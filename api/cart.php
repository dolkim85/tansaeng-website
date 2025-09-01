<?php
// 장바구니 API
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다']);
    exit;
}

$currentUser = $auth->getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    switch ($method) {
        case 'GET':
            // 장바구니 목록 조회
            $sql = "SELECT ci.*, p.name, p.description, p.price, p.image_url, p.stock, c.name as category_name
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.id
                    LEFT JOIN product_categories c ON p.category_id = c.id
                    WHERE ci.user_id = ? AND p.status = 'active'
                    ORDER BY ci.created_at DESC";
            
            $cartItems = $db->select($sql, [$currentUser['id']]);
            
            // 장바구니 총액 계산
            $totalAmount = 0;
            $totalItems = 0;
            
            foreach ($cartItems as &$item) {
                $item['subtotal'] = $item['price'] * $item['quantity'];
                $totalAmount += $item['subtotal'];
                $totalItems += $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'cart_items' => $cartItems,
                'summary' => [
                    'total_items' => $totalItems,
                    'total_amount' => $totalAmount,
                    'item_count' => count($cartItems)
                ]
            ]);
            break;
            
        case 'POST':
            // 장바구니에 상품 추가
            if (empty($input['product_id'])) {
                throw new Exception('상품 ID가 필요합니다');
            }
            
            $productId = intval($input['product_id']);
            $quantity = max(1, intval($input['quantity'] ?? 1));
            
            // 상품 존재 여부 및 재고 확인
            $product = $db->selectOne(
                "SELECT id, name, price, stock FROM products WHERE id = ? AND status = 'active'",
                [$productId]
            );
            
            if (!$product) {
                throw new Exception('상품을 찾을 수 없습니다');
            }
            
            if ($product['stock'] < $quantity) {
                throw new Exception('재고가 부족합니다');
            }
            
            // 이미 장바구니에 있는지 확인
            $existingItem = $db->selectOne(
                "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?",
                [$currentUser['id'], $productId]
            );
            
            if ($existingItem) {
                // 기존 수량 업데이트
                $newQuantity = $existingItem['quantity'] + $quantity;
                
                if ($product['stock'] < $newQuantity) {
                    throw new Exception('재고가 부족합니다');
                }
                
                $db->update(
                    'cart_items', 
                    ['quantity' => $newQuantity], 
                    'id = ?', 
                    [$existingItem['id']]
                );
                
                $message = '장바구니 수량이 업데이트되었습니다';
            } else {
                // 새 아이템 추가
                $db->insert('cart_items', [
                    'user_id' => $currentUser['id'],
                    'product_id' => $productId,
                    'quantity' => $quantity
                ]);
                
                $message = '장바구니에 상품이 추가되었습니다';
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            break;
            
        case 'PUT':
            // 장바구니 수량 수정
            if (empty($input['cart_item_id'])) {
                throw new Exception('장바구니 아이템 ID가 필요합니다');
            }
            
            $cartItemId = intval($input['cart_item_id']);
            $quantity = max(1, intval($input['quantity'] ?? 1));
            
            // 장바구니 아이템 확인
            $cartItem = $db->selectOne(
                "SELECT ci.*, p.stock FROM cart_items ci 
                 JOIN products p ON ci.product_id = p.id 
                 WHERE ci.id = ? AND ci.user_id = ?",
                [$cartItemId, $currentUser['id']]
            );
            
            if (!$cartItem) {
                throw new Exception('장바구니 아이템을 찾을 수 없습니다');
            }
            
            if ($cartItem['stock'] < $quantity) {
                throw new Exception('재고가 부족합니다');
            }
            
            $db->update('cart_items', ['quantity' => $quantity], 'id = ?', [$cartItemId]);
            
            echo json_encode([
                'success' => true,
                'message' => '수량이 변경되었습니다'
            ]);
            break;
            
        case 'DELETE':
            // 장바구니에서 상품 제거
            $cartItemId = intval($_GET['id'] ?? 0);
            
            if ($cartItemId <= 0) {
                throw new Exception('유효한 장바구니 아이템 ID가 필요합니다');
            }
            
            // 사용자 소유 확인
            $cartItem = $db->selectOne(
                "SELECT id FROM cart_items WHERE id = ? AND user_id = ?",
                [$cartItemId, $currentUser['id']]
            );
            
            if (!$cartItem) {
                throw new Exception('장바구니 아이템을 찾을 수 없습니다');
            }
            
            $db->delete('cart_items', 'id = ?', [$cartItemId]);
            
            echo json_encode([
                'success' => true,
                'message' => '장바구니에서 상품이 제거되었습니다'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => '지원하지 않는 요청 방식입니다']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
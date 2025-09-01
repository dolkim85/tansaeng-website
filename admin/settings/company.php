<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
$auth = Auth::getInstance();
$auth->requireAdmin();

require_once $base_path . '/classes/Database.php';

$success = '';
$error = '';
$company_info = null;

// 회사 정보 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($title)) {
        $error = '회사 소개 제목을 입력해주세요.';
    } elseif (empty($content)) {
        $error = '회사 소개 내용을 입력해주세요.';
    } else {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 기존 회사 정보가 있는지 확인
            $stmt = $pdo->query("SELECT id FROM company_info LIMIT 1");
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 업데이트
                $sql = "UPDATE company_info SET title = ?, content = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $content, $existing['id']]);
            } else {
                // 새로 생성
                $sql = "INSERT INTO company_info (title, content, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $content]);
            }
            
            $success = '회사 소개가 저장되었습니다.';
            
        } catch (Exception $e) {
            $error = '회사 소개 저장에 실패했습니다: ' . $e->getMessage();
        }
    }
}

// 현재 회사 정보 불러오기
try {
    $pdo = Database::getInstance()->getConnection();
    
    $stmt = $pdo->query("SELECT * FROM company_info ORDER BY id DESC LIMIT 1");
    $company_info = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Failed to load company info: " . $e->getMessage());
}

// 기본값 설정
if (!$company_info) {
    $company_info = [
        'title' => '탄생과 함께하는 스마트팜',
        'content' => '탄생은 최첨단 스마트팜 기술을 통해 지속가능한 농업의 미래를 만들어갑니다.

🌱 **잘자라 배지**
식물이 좋아하는 배지로 건강한 작물을 키워보세요. 재생 섬유를 활용한 친환경 배지로 뛰어난 통기성과 보습성을 제공합니다.

🤖 **AI 식물분석**
첨단 AI 기술과 라즈베리파이 카메라를 활용한 실시간 식물 건강상태 분석 서비스를 제공합니다.
- 실시간 식물 촬영 및 모니터링
- AI 기반 병충해 및 영양상태 진단
- 환경 센서 데이터 통합 분석
- 맞춤형 관리 솔루션 제공

📱 **스마트 모니터링**
온도, 습도, pH, EC 등 환경 데이터를 실시간으로 모니터링하고 관리할 수 있는 통합 시스템을 제공합니다.

**우리의 비전**
농업과 기술의 조화를 통해 더 나은 미래를 만들어가는 것이 탄생의 목표입니다. 지속가능한 농업 솔루션으로 농부들의 성공적인 작물 재배를 지원하고, 안전하고 건강한 농산물 생산에 기여합니다.'
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회사 소개 관리 - 탄생 관리자</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/korean-editor.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .admin-content {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .page-title {
            margin: 0;
            color: #333;
            font-size: 1.8rem;
        }
        
        .page-subtitle {
            color: #666;
            margin-top: 5px;
        }
        
        .company-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .btn-outline {
            background-color: transparent;
            color: #007bff;
            border: 1px solid #007bff;
        }
        
        .btn-outline:hover {
            background-color: #007bff;
            color: white;
        }
        
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .form-help {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        .editor-container {
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .preview-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .preview-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .preview-content {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">🏢 회사 소개 관리</h1>
                <p class="page-subtitle">메인페이지에 표시될 회사 소개를 작성하고 관리합니다</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>성공:</strong> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>오류:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form class="company-form" method="post">
                <div class="form-section">
                    <h3 class="form-section-title">기본 정보</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="title">회사 소개 제목 *</label>
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?= htmlspecialchars($company_info['title']) ?>" required
                               placeholder="예: 탄생과 함께하는 스마트팜">
                        <div class="form-help">메인페이지 회사 소개 섹션의 제목으로 사용됩니다.</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">회사 소개 내용</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="content">소개 내용 *</label>
                        <div class="form-help">마크다운 형식을 지원합니다. **굵은글씨**, *기울임*, - 목록 등을 사용할 수 있습니다.</div>
                        
                        <div class="editor-container">
                            <div class="korean-editor" id="companyEditor">
                                <div class="editor-toolbar">
                                    <div class="toolbar-group">
                                        <button type="button" onclick="formatText('bold')" title="굵게">
                                            <strong>B</strong>
                                        </button>
                                        <button type="button" onclick="formatText('italic')" title="기울임">
                                            <em>I</em>
                                        </button>
                                        <button type="button" onclick="formatText('underline')" title="밑줄">
                                            <u>U</u>
                                        </button>
                                    </div>
                                    
                                    <div class="toolbar-group">
                                        <select onchange="formatText('fontSize', this.value)" title="글자 크기">
                                            <option value="">크기</option>
                                            <option value="12px">12px</option>
                                            <option value="14px">14px</option>
                                            <option value="16px">16px</option>
                                            <option value="18px">18px</option>
                                            <option value="20px">20px</option>
                                            <option value="24px">24px</option>
                                            <option value="28px">28px</option>
                                        </select>
                                        
                                        <input type="color" onchange="formatText('foreColor', this.value)" title="글자 색상">
                                    </div>
                                    
                                    <div class="toolbar-group">
                                        <button type="button" onclick="formatText('justifyLeft')" title="왼쪽 정렬">
                                            ≡
                                        </button>
                                        <button type="button" onclick="formatText('justifyCenter')" title="가운데 정렬">
                                            ≡
                                        </button>
                                        <button type="button" onclick="formatText('justifyRight')" title="오른쪽 정렬">
                                            ≡
                                        </button>
                                    </div>
                                    
                                    <div class="toolbar-group">
                                        <button type="button" onclick="formatText('insertUnorderedList')" title="목록">
                                            • 목록
                                        </button>
                                        <button type="button" onclick="formatText('insertOrderedList')" title="번호 목록">
                                            1. 목록
                                        </button>
                                    </div>
                                    
                                    <div class="toolbar-group">
                                        <button type="button" onclick="insertEmoji('🌱')" title="이모지">🌱</button>
                                        <button type="button" onclick="insertEmoji('🤖')" title="이모지">🤖</button>
                                        <button type="button" onclick="insertEmoji('📱')" title="이모지">📱</button>
                                        <button type="button" onclick="insertEmoji('✓')" title="이모지">✓</button>
                                    </div>
                                </div>
                                
                                <div class="editor-content" 
                                     contenteditable="true" 
                                     id="editorContent"
                                     style="min-height: 400px; max-height: 600px; overflow-y: auto; padding: 20px; border-top: 1px solid #ddd;"
                                     placeholder="회사 소개 내용을 입력하세요..."><?= htmlspecialchars($company_info['content']) ?></div>
                            </div>
                        </div>
                        
                        <textarea name="content" id="hiddenContent" style="display: none;" required></textarea>
                    </div>
                </div>
                
                <div class="preview-section">
                    <h4 class="preview-title">미리보기</h4>
                    <div class="preview-content" id="previewContent">
                        <?= nl2br(htmlspecialchars($company_info['content'])) ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">💾 저장하기</button>
                    <a href="/admin/settings/" class="btn btn-outline">⚙️ 설정으로 돌아가기</a>
                    <button type="button" onclick="previewOnMainPage()" class="btn btn-primary">👁️ 메인페이지에서 미리보기</button>
                </div>
            </form>
        </main>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/korean-editor.js"></script>
    <script>
        // 에디터 초기화
        const editor = document.getElementById('editorContent');
        const hiddenContent = document.getElementById('hiddenContent');
        const previewContent = document.getElementById('previewContent');
        
        // 에디터 내용 변경시 미리보기 업데이트
        editor.addEventListener('input', function() {
            const content = this.textContent || this.innerText || '';
            hiddenContent.value = content;
            previewContent.innerHTML = content.replace(/\n/g, '<br>');
        });
        
        // 폼 제출시 에디터 내용 저장
        document.querySelector('form').addEventListener('submit', function(e) {
            const content = editor.textContent || editor.innerText || '';
            hiddenContent.value = content;
        });
        
        // 텍스트 서식 적용
        function formatText(command, value = null) {
            if (command === 'justifyCenter') {
                // 가운데 정렬 시 수직으로 되는 문제 해결
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    const selectedText = range.toString();
                    
                    if (selectedText) {
                        const span = document.createElement('span');
                        span.style.display = 'block';
                        span.style.textAlign = 'center';
                        span.style.writingMode = 'horizontal-tb';
                        span.style.direction = 'ltr';
                        span.textContent = selectedText;
                        
                        range.deleteContents();
                        range.insertNode(span);
                        
                        selection.removeAllRanges();
                        const newRange = document.createRange();
                        newRange.selectNodeContents(span);
                        selection.addRange(newRange);
                    }
                }
            } else {
                document.execCommand(command, false, value);
            }
            editor.focus();
        }
        
        // 이모지 삽입
        function insertEmoji(emoji) {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                range.deleteContents();
                range.insertNode(document.createTextNode(emoji));
                range.collapse(false);
            }
            editor.focus();
        }
        
        // 메인페이지에서 미리보기
        function previewOnMainPage() {
            window.open('/', '_blank');
        }
        
        // 초기 미리보기 설정
        const initialContent = editor.textContent || editor.innerText || '';
        hiddenContent.value = initialContent;
        previewContent.innerHTML = initialContent.replace(/\n/g, '<br>');
    </script>
</body>
</html>
<?php
class SocialLogin {
    private $config;
    private $pdo;
    
    public function __construct() {
        $this->config = include dirname(__DIR__) . '/config/social_config.php';
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    /**
     * 구글 로그인 URL 생성
     */
    public function getGoogleLoginUrl() {
        $params = [
            'client_id' => $this->config['google']['client_id'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'scope' => $this->config['google']['scope'],
            'response_type' => 'code',
            'access_type' => 'online'
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * 카카오 로그인 URL 생성
     */
    public function getKakaoLoginUrl() {
        $params = [
            'client_id' => $this->config['kakao']['client_id'],
            'redirect_uri' => $this->config['kakao']['redirect_uri'],
            'response_type' => 'code'
        ];
        
        return 'https://kauth.kakao.com/oauth/authorize?' . http_build_query($params);
    }
    
    /**
     * 네이버 로그인 URL 생성
     */
    public function getNaverLoginUrl() {
        $state = bin2hex(random_bytes(16));
        $_SESSION['naver_state'] = $state;
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['naver']['client_id'],
            'redirect_uri' => $this->config['naver']['redirect_uri'],
            'state' => $state
        ];
        
        return 'https://nid.naver.com/oauth2.0/authorize?' . http_build_query($params);
    }
    
    /**
     * 구글 OAuth 콜백 처리
     */
    public function handleGoogleCallback($code) {
        try {
            // 1. 액세스 토큰 요청
            $tokenData = $this->getGoogleAccessToken($code);
            if (!$tokenData || !isset($tokenData['access_token'])) {
                throw new Exception('Failed to get access token');
            }
            
            // 2. 사용자 정보 가져오기
            $userInfo = $this->getGoogleUserInfo($tokenData['access_token']);
            if (!$userInfo) {
                throw new Exception('Failed to get user info');
            }
            
            // 3. 사용자 등록 또는 로그인 처리
            return $this->processUser('google', $userInfo['id'], [
                'email' => $userInfo['email'],
                'username' => $userInfo['name'],
                'avatar_url' => $userInfo['picture'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log('Google login error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 카카오 OAuth 콜백 처리
     */
    public function handleKakaoCallback($code) {
        try {
            // 1. 액세스 토큰 요청
            $tokenData = $this->getKakaoAccessToken($code);
            if (!$tokenData || !isset($tokenData['access_token'])) {
                throw new Exception('Failed to get access token');
            }
            
            // 2. 사용자 정보 가져오기
            $userInfo = $this->getKakaoUserInfo($tokenData['access_token']);
            if (!$userInfo) {
                throw new Exception('Failed to get user info');
            }
            
            // 3. 사용자 등록 또는 로그인 처리
            return $this->processUser('kakao', $userInfo['id'], [
                'email' => $userInfo['kakao_account']['email'] ?? null,
                'username' => $userInfo['properties']['nickname'] ?? '카카오사용자',
                'avatar_url' => $userInfo['properties']['profile_image'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log('Kakao login error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 네이버 OAuth 콜백 처리
     */
    public function handleNaverCallback($code, $state) {
        try {
            // state 확인
            if (!isset($_SESSION['naver_state']) || $_SESSION['naver_state'] !== $state) {
                throw new Exception('Invalid state parameter');
            }
            
            // 1. 액세스 토큰 요청
            $tokenData = $this->getNaverAccessToken($code, $state);
            if (!$tokenData || !isset($tokenData['access_token'])) {
                throw new Exception('Failed to get access token');
            }
            
            // 2. 사용자 정보 가져오기
            $userInfo = $this->getNaverUserInfo($tokenData['access_token']);
            if (!$userInfo || !isset($userInfo['response'])) {
                throw new Exception('Failed to get user info');
            }
            
            $user = $userInfo['response'];
            
            // 3. 사용자 등록 또는 로그인 처리
            return $this->processUser('naver', $user['id'], [
                'email' => $user['email'] ?? null,
                'username' => $user['name'] ?? $user['nickname'] ?? '네이버사용자',
                'avatar_url' => $user['profile_image'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log('Naver login error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 구글 액세스 토큰 요청
     */
    private function getGoogleAccessToken($code) {
        $postData = [
            'client_id' => $this->config['google']['client_id'],
            'client_secret' => $this->config['google']['client_secret'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * 구글 사용자 정보 요청
     */
    private function getGoogleUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * 카카오 액세스 토큰 요청
     */
    private function getKakaoAccessToken($code) {
        $postData = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->config['kakao']['client_id'],
            'redirect_uri' => $this->config['kakao']['redirect_uri'],
            'code' => $code
        ];
        
        if (!empty($this->config['kakao']['client_secret'])) {
            $postData['client_secret'] = $this->config['kakao']['client_secret'];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://kauth.kakao.com/oauth/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * 카카오 사용자 정보 요청
     */
    private function getKakaoUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://kapi.kakao.com/v2/user/me');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * 네이버 액세스 토큰 요청
     */
    private function getNaverAccessToken($code, $state) {
        $postData = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->config['naver']['client_id'],
            'client_secret' => $this->config['naver']['client_secret'],
            'code' => $code,
            'state' => $state
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://nid.naver.com/oauth2.0/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * 네이버 사용자 정보 요청
     */
    private function getNaverUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openapi.naver.com/v1/nid/me');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * 사용자 등록 또는 로그인 처리
     */
    private function processUser($provider, $socialId, $userData) {
        try {
            // 기존 소셜 계정 확인
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE social_provider = ? AND social_id = ?");
            $stmt->execute([$provider, $socialId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // 기존 사용자 - 정보 업데이트
                $stmt = $this->pdo->prepare("
                    UPDATE users SET 
                        username = COALESCE(NULLIF(?, ''), username),
                        avatar_url = COALESCE(NULLIF(?, ''), avatar_url),
                        last_login = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$userData['username'], $userData['avatar_url'], $user['id']]);
                
                return $user;
            } else {
                // 이메일로 기존 계정 확인
                if (!empty($userData['email'])) {
                    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$userData['email']]);
                    $existingUser = $stmt->fetch();
                    
                    if ($existingUser) {
                        // 기존 계정에 소셜 로그인 연결
                        $stmt = $this->pdo->prepare("
                            UPDATE users SET 
                                social_provider = ?, 
                                social_id = ?,
                                avatar_url = COALESCE(NULLIF(?, ''), avatar_url),
                                last_login = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ");
                        $stmt->execute([$provider, $socialId, $userData['avatar_url'], $existingUser['id']]);
                        
                        return $existingUser;
                    }
                }
                
                // 새 사용자 생성
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, social_provider, social_id, avatar_url, created_at) 
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([
                    $userData['username'],
                    $userData['email'],
                    $provider,
                    $socialId,
                    $userData['avatar_url']
                ]);
                
                $userId = $this->pdo->lastInsertId();
                
                // 새로 생성된 사용자 정보 반환
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                
                return $stmt->fetch();
            }
        } catch (Exception $e) {
            error_log('Process user error: ' . $e->getMessage());
            return false;
        }
    }
}
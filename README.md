# 탄생(Tangsaeng) - 스마트팜 웹사이트

스마트팜 배지 제조 전문회사 탄생의 공식 웹사이트입니다.

## 주요 기능

- **제품 쇼케이스**: 스마트팜 배지 제품 소개
- **AI 식물분석**: 라즈베리파이 카메라를 활용한 식물 건강상태 분석
- **온라인 스토어**: 제품 주문 및 구매 시스템
- **사용자 관리**: 회원가입, 로그인, 소셜 로그인 지원
- **관리자 페이지**: 제품, 사용자, 콘텐츠 관리
- **게시판 시스템**: 공지사항 및 커뮤니티

## 기술 스택

- **백엔드**: PHP 8+
- **데이터베이스**: MySQL
- **프론트엔드**: HTML5, CSS3, JavaScript
- **인증**: Firebase Auth (Google, Kakao, Naver)
- **배포**: Vercel
- **도메인**: www.tansaeng.com

## 배포 환경

이 프로젝트는 Vercel에서 호스팅되며 다음 환경변수가 필요합니다:

- `DB_HOST`: 데이터베이스 호스트
- `DB_NAME`: 데이터베이스 이름
- `DB_USER`: 데이터베이스 사용자명
- `DB_PASS`: 데이터베이스 비밀번호

## 로컬 개발

```bash
# 웹서버 실행
php -S localhost:8080

# 데이터베이스 설정
# localhost MySQL 서버에 tangsaeng_db 데이터베이스 생성 필요
```

## 라이선스

© 2024 탄생(Tangsaeng). All rights reserved.
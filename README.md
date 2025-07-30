# SyncTrust Monorepo

這是一個包含後端和前端專案的 Monorepo 架構。

## 專案結構

```
synctrust-monorepo/
├── packages/
│   ├── backend/          # PHP CodeIgniter 後端
│   └── frontend/         # Vue.js 前端
├── package.json          # Monorepo 根配置
└── README.md            # 專案說明
```

## 快速開始

### 安裝依賴

```bash
# 安裝所有依賴（Node.js 和 PHP）
npm run install:all
```

### 開發模式

```bash
# 同時啟動後端和前端開發伺服器
npm run dev

# 或分別啟動
npm run dev:backend    # 啟動 PHP 後端
npm run dev:frontend   # 啟動 Vue.js 前端
```

### 建置

```bash
# 建置所有專案
npm run build

# 或分別建置
npm run build:backend
npm run build:frontend
```

### 測試

```bash
# 執行所有測試
npm run test

# 或分別測試
npm run test:backend
npm run test:frontend
```

## 技術棧

### 後端 (packages/backend)
- PHP 8.1+
- CodeIgniter 4
- MySQL/MariaDB
- IPFS 整合
- 區塊鏈整合

### 前端 (packages/frontend)
- Vue.js 3
- Vite
- TypeScript
- Element Plus UI

## 開發指南

### 後端開發
```bash
cd packages/backend
composer install
php spark serve
```

### 前端開發
```bash
cd packages/frontend
npm install
npm run dev
```

## 部署

### 生產環境建置
```bash
npm run build
```

### Docker 部署
```bash
# 建置 Docker 映像
docker build -t synctrust-monorepo .

# 執行容器
docker run -p 8080:80 synctrust-monorepo
```

## 環境變數

請確保在 `packages/backend/.env` 和 `packages/frontend/.env` 中設定正確的環境變數。

## 貢獻

1. Fork 專案
2. 建立功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交變更 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 開啟 Pull Request

## 授權

此專案採用 MIT 授權 - 詳見 [LICENSE](LICENSE) 檔案。

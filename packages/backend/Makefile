.PHONY: help install dev build test clean docker-up docker-down docker-dev-up docker-dev-down

# 預設目標
help:
	@echo "SyncTrust Monorepo 管理命令"
	@echo ""
	@echo "安裝:"
	@echo "  make install          - 安裝所有依賴"
	@echo ""
	@echo "開發:"
	@echo "  make dev              - 啟動開發環境"
	@echo "  make dev-backend      - 啟動後端開發伺服器"
	@echo "  make dev-frontend     - 啟動前端開發伺服器"
	@echo ""
	@echo "建置:"
	@echo "  make build            - 建置所有專案"
	@echo "  make build-backend    - 建置後端"
	@echo "  make build-frontend   - 建置前端"
	@echo ""
	@echo "測試:"
	@echo "  make test             - 執行所有測試"
	@echo "  make test-backend     - 執行後端測試"
	@echo "  make test-frontend    - 執行前端測試"
	@echo ""
	@echo "Docker:"
	@echo "  make docker-up        - 啟動生產環境 Docker"
	@echo "  make docker-down      - 停止生產環境 Docker"
	@echo "  make docker-dev-up    - 啟動開發環境 Docker"
	@echo "  make docker-dev-down  - 停止開發環境 Docker"
	@echo ""
	@echo "清理:"
	@echo "  make clean            - 清理建置檔案"

# 安裝依賴
install:
	@echo "安裝所有依賴..."
	npm run install:all

# 開發環境
dev:
	@echo "啟動開發環境..."
	npm run dev

dev-backend:
	@echo "啟動後端開發伺服器..."
	npm run dev:backend

dev-frontend:
	@echo "啟動前端開發伺服器..."
	npm run dev:frontend

# 建置
build:
	@echo "建置所有專案..."
	npm run build

build-backend:
	@echo "建置後端..."
	npm run build:backend

build-frontend:
	@echo "建置前端..."
	npm run build:frontend

# 測試
test:
	@echo "執行所有測試..."
	npm run test

test-backend:
	@echo "執行後端測試..."
	npm run test:backend

test-frontend:
	@echo "執行前端測試..."
	npm run test:frontend

# Docker 生產環境
docker-up:
	@echo "啟動生產環境 Docker..."
	docker-compose up -d

docker-down:
	@echo "停止生產環境 Docker..."
	docker-compose down

# Docker 開發環境
docker-dev-up:
	@echo "啟動開發環境 Docker..."
	docker-compose -f docker-compose.dev.yml up -d

docker-dev-down:
	@echo "停止開發環境 Docker..."
	docker-compose -f docker-compose.dev.yml down

# 清理
clean:
	@echo "清理建置檔案..."
	rm -rf packages/backend/vendor
	rm -rf packages/frontend/node_modules
	rm -rf packages/frontend/dist
	rm -rf node_modules
	@echo "清理完成" 
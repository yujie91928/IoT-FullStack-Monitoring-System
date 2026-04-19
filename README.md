# 全棧物聯網即時環境監控系統 IoT Full-Stack Monitoring System
(ESP32 + DHT22 + MQTT + .NET + PHP)

[![License](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Docker](https://img.shields.io/badge/Docker-Supported-blue?logo=docker)](https://www.docker.com/)
[![Mosquitto](https://img.shields.io/badge/Broker-Mosquitto-3C5280?style=for-the-badge&logo=eclipsemosquitto&logoColor=white)](https://mosquitto.org/)
[![.NET](https://img.shields.io/badge/.NET%20Core-8.0-purple?logo=dotnet)](https://dotnet.microsoft.com/en-us/download/dotnet/8.0)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-blue?logo=postgresql)](https://www.postgresql.org/)

## 📖 專案簡介
本專案實作了一套完整的 **工業物聯網 (IoT)** 數據採集與監控方案。從硬體端傳感器採集、傳輸協議校準、後端非同步入庫到前端即時數據可視化，展示了跨平台技術整合。



### 核心功能
* **數據即時採集**：ESP32 驅動 DHT22，並透過 MQTT 協議即時發送環境數據。
* **異步處理架構**：ASP.NET Core Worker Service 持續訂閱 MQTT Topic，確保高頻率數據下入庫的穩定性。
* **設備在線偵測**：透過 MQTT LWT (Last Will and Testament) 與數據時間戳判斷設備連接狀態。

---

## 🛠 技術架構 (System Architecture)

1.  **感知層 (Hardware)**: ESP32, DHT22 Sensor (採集溫度、濕度、Heartbeat)。
2.  **通訊層 (Protocol)**: MQTT (Mosquitto Broker)，實現低頻寬、低延遲的數據交換。
3.  **後端服務 (Backend)**: ASP.NET Core 實作 MQTT Client 訂閱，負責邏輯過濾與 PostgreSQL 存取。
4.  **數據層 (Database)**: PostgreSQL 關聯式資料庫，儲存結構化遙測數據。
5.  **展示層 (Frontend)**: PHP, HTML。

---

## 📂 目錄結構說明
```text
.
├── firmware/              # ESP32 Arduino 韌體程式碼
├── backend-service/       # ASP.NET Core MQTT 數據處理服務
├── web-ui/                # Web 前端監控介面 (PHP/JS)
│   ├── index.php          # 即時監控儀表板 (Dashboard)
│   └── api.php            # 數據傳輸介面 (RESTful API)
├── database/              # PostgreSQL Table Schema 定義
└── README.md              # 專案詳細文件
```
---
## 📜 架構圖
<img width="430" height="300" alt="mqtt架構圖" src="https://github.com/user-attachments/assets/4c1e154a-d311-4816-bbb4-0bbc0a970a99" />

---
## 🖥️ 畫面展示
設備在線
<img width="460" height="350" alt="擷取o" src="https://github.com/user-attachments/assets/ad1d94de-0317-4a18-8da7-3b4a1ddf19c2" />
設備離線(斷電後約15秒執行LWT)透過Broker發送設備離線訊息
<img width="460" height="350" alt="擷取斷" src="https://github.com/user-attachments/assets/5bfd077a-2441-497f-bcf4-3f4e062de133" />
可勾選只查看溫度或濕度
<img width="460" height="350" alt="2" src="https://github.com/user-attachments/assets/8a33c332-cd76-4ed3-b3c1-fa2c93b0976d" />
ESP32+DHT22實體
<img width="460" height="350" alt="f6b89a05-c8d0-4376-9ae7-14a456f59043" src="https://github.com/user-attachments/assets/4b721006-36c8-499b-a33a-969de9921e76" />


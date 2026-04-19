<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 智能環境監控</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&family=Roboto:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --temp-color: #ff7e5f;
            --humi-color: #00c6ff;
            --bg-color: #f5f7fa;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-sub: #636e72;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Noto Sans TC', 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 900px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 700;
            color: var(--text-main);
            border-left: 5px solid var(--primary-color);
            padding-left: 15px;
        }

        /* 狀態指示標籤 */
        #status-box {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .online { background: #e3f9e5; color: #1f8b24; border: 1px solid #1f8b24; }
        .offline { background: #ffe3e3; color: #d63031; border: 1px solid #d63031; }

        /* 數據卡片區 */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.2s;
        }

        .card:hover { transform: translateY(-5px); }

        .label { font-size: 1rem; color: var(--text-sub); margin-bottom: 10px; }
        .value { font-size: 4rem; font-weight: 700; font-family: 'Roboto', sans-serif; line-height: 1; }
        .unit { font-size: 1.2rem; margin-left: 5px; color: var(--text-sub); font-weight: 400; }

        /* 圖表區域 */
        .chart-section {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .footer-info {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-sub);
        }

        #last-update { font-weight: bold; color: var(--primary-color); }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>環境監控儀表板</h1>
        <div id="status-box" class="offline">正在嘗試連線...</div>
    </header>

    <div class="grid">
        <div class="card">
            <div class="label">當前溫度</div>
            <div>
                <span class="value" id="temp-val" style="color: var(--temp-color);">--</span>
                <span class="unit">°C</span>
            </div>
        </div>
        <div class="card">
            <div class="label">相對濕度</div>
            <div>
                <span class="value" id="humi-val" style="color: var(--humi-color);">--</span>
                <span class="unit">%</span>
            </div>
        </div>
    </div>

    <div class="chart-section">
        <canvas id="liveChart" height="120"></canvas>
        <div class="footer-info">
            <span>數據來源: ESP32 + DHT22</span>
            <span>最後同步: <span id="last-update">--</span></span>
        </div>
    </div>
</div>

<script>
let lastChartSecond = ""; 

// 圖表預設樣式優化
const ctx = document.getElementById('liveChart').getContext('2d');
const myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            { 
                label: '溫度 (°C)', 
                data: [], 
                borderColor: '#ff7e5f',
                backgroundColor: 'rgba(255, 126, 95, 0.1)',
                borderWidth: 4,
                pointBackgroundColor: '#ff7e5f',
                pointRadius: 2,
                fill: true,
                tension: 0.4 
            },
            { 
                label: '濕度 (%)', 
                data: [], 
                borderColor: '#00c6ff', 
                backgroundColor: 'rgba(0, 198, 255, 0.1)',
                borderWidth: 4,
                pointBackgroundColor: '#00c6ff',
                pointRadius: 2,
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', align: 'end' }
        },
        scales: {
            y: {
                beginAtZero: false,
                grid: { color: '#f0f0f0' },
                ticks: { font: { size: 10 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 } }
            }
        }
    }
});

function updateData() {
    fetch('api.php')
        .then(res => res.json())
        .then(data => {
            if (!data || data.error) return;

            // 1. 更新數值
            document.getElementById('temp-val').innerText = data.temp;
            document.getElementById('humi-val').innerText = data.humi;
            document.getElementById('last-update').innerText = data.full_time;
            
            // 2. 更新狀態標籤
            const sBox = document.getElementById('status-box');
            if (data.status === '在線') {
                sBox.innerText = "● 設備在線";
                sBox.className = 'online';
            } else {
                sBox.innerText = "○ 設備離線";
                sBox.className = 'offline';
            }

            // 3. 圖表更新判斷 (秒級)
            if (data.chart_time !== lastChartSecond && data.chart_time !== "--:--:--") {
                lastChartSecond = data.chart_time;
                
                const maxPoints = 15; 
                if (myChart.data.labels.length >= maxPoints) {
                    myChart.data.labels.shift();
                    myChart.data.datasets[0].data.shift();
                    myChart.data.datasets[1].data.shift();
                }
                
                myChart.data.labels.push(data.chart_time);
                myChart.data.datasets[0].data.push(data.temp);
                myChart.data.datasets[1].data.push(data.humi);
                myChart.update('none'); // 使用無動畫模式讓資料更新更流暢
            }
        })
        .catch(err => {
            console.error('API 連線失敗:', err);
            document.getElementById('status-box').innerText = "連線異常";
            document.getElementById('status-box').className = 'offline';
        });
}

// 每秒輪詢一次 API
setInterval(updateData, 1000);
updateData();
</script>

</body>
</html>
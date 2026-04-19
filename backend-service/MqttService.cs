using MQTTnet; // 引入 MQTTnet 核心函式庫，用來處理 MQTT 通訊
using MQTTnet.Protocol; // 引入 MQTT 協定相關類別與設定
using Microsoft.Extensions.Configuration; // 用來讀取 appsettings.json 等設定檔
using Microsoft.Extensions.Hosting; // 提供 BackgroundService 等背景服務基底類別
using Npgsql; // PostgreSQL 資料庫連線與操作套件
using System.Text; // 提供字串與編碼相關功能（此程式目前未直接使用）

public class MqttService : BackgroundService // 建立 MQTT 背景服務，繼承 BackgroundService
{
    //宣告變數欄位
    private readonly string _connectionString; // PostgreSQL 連線字串
    private readonly string _mqttServer; // MQTT Broker 位址
    private readonly int _mqttPort; // MQTT 連線埠號
    private readonly string _topicFilter; // MQTT 訂閱的 topic 篩選條件

    //變數賦值
    public MqttService(IConfiguration configuration) // 建構子，從appsettings.json設定檔取得參數
    {
        _connectionString = configuration.GetConnectionString("Postgres")
            ?? throw new InvalidOperationException("找不到Postgres資料庫連接參數"); // 若未設定資料庫連接參數則拋出例外

        _mqttServer = configuration.GetValue<string>("Mqtt:Broker") ?? "192.168.6.35"; // 取得 MQTT Broker 位址
        _mqttPort = configuration.GetValue<int?>("Mqtt:Port") ?? 1883; // 取得 MQTT Port
        _topicFilter = configuration.GetValue<string>("Mqtt:Topic") ?? "/esp32/#"; // 訂閱 topic為 esp32 所有子 topic
    }

    protected override async Task ExecuteAsync(CancellationToken stoppingToken) // 背景服務主執行方法
    {
        var factory = new MqttClientFactory(); // 建立 MQTT Client 工廠
        using var client = factory.CreateMqttClient(); // 建立 MQTT Client 並在結束時釋放

        var options = new MqttClientOptionsBuilder() // 建立 MQTT 連線設定
            .WithTcpServer(_mqttServer, _mqttPort) // 設定 MQTT Broker 位址與 port
            .Build(); // 建立設定物件

        client.ApplicationMessageReceivedAsync += async e => // 設定收到 MQTT 訊息時的處理事件(事件驅動)
        {
            string topic = e.ApplicationMessage.Topic ?? string.Empty; // 取得訊息 topic
            string payload = e.ApplicationMessage.ConvertPayloadToString(); // 轉換 payload 為字串

            Console.WriteLine($"Topic: {topic}"); // 輸出 topic
            Console.WriteLine($"Data : {payload}"); // 輸出資料內容
            Console.WriteLine($"------------------------------");

            await SaveMessageAsync(topic, payload, stoppingToken); // 將資料寫入資料庫
        };

        try
        {
            await EnsureDatabaseTableAsync(stoppingToken); // 確保資料表存在

            await client.ConnectAsync(options, stoppingToken); // 連線到 MQTT Broker
            
            var subscribeOptions = factory.CreateSubscribeOptionsBuilder() // 建立訂閱設定
                .WithTopicFilter(f => f.WithTopic(_topicFilter)) // 設定訂閱的 topic 規則
                .Build(); // 建立訂閱設定物件

            await client.SubscribeAsync(subscribeOptions, stoppingToken); // 訂閱 MQTT topic

            Console.WriteLine("MQTT 已連線，等待資料..."); // 顯示連線成功訊息

            await Task.Delay(Timeout.Infinite, stoppingToken); // 保持服務持續運行
        }
        catch (Exception ex)
        {
            Console.WriteLine($"MQTT 連線失敗: {ex.Message}"); // 顯示錯誤訊息
        }
    }

    private async Task EnsureDatabaseTableAsync(CancellationToken cancellationToken) // 建立資料表（若不存在）
    {
        await using var connection = new NpgsqlConnection(_connectionString); // 建立 PostgreSQL 連線
        await connection.OpenAsync(cancellationToken); // 開啟資料庫連線

        const string sql = @"
CREATE TABLE IF NOT EXISTS mqtt_messages (
    id SERIAL PRIMARY KEY,
    topic TEXT NOT NULL,
    payload TEXT,
    received_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);"; // 建立 MQTT 訊息儲存表（若不存在）

        await using var command = new NpgsqlCommand(sql, connection); // 建立 SQL 命令
        await command.ExecuteNonQueryAsync(cancellationToken); // 執行建立資料表
    }

    private async Task SaveMessageAsync(string topic, string payload, CancellationToken cancellationToken) // 儲存 MQTT 訊息到資料庫
    {
        try
        {
            await using var connection = new NpgsqlConnection(_connectionString); // 建立資料庫連線
            await connection.OpenAsync(cancellationToken); // 開啟連線

            await using var command = new NpgsqlCommand(
                "INSERT INTO mqtt_messages (topic, payload) VALUES (@topic, @payload)", // 插入資料 SQL
                connection);
            command.Parameters.AddWithValue("@topic", topic); // 綁定 topic 參數
            command.Parameters.AddWithValue("@payload", payload ?? string.Empty); // 綁定 payload 參數（避免 null）

            await command.ExecuteNonQueryAsync(cancellationToken); // 執行新增資料
        }
        catch (Exception ex)
        {
            Console.WriteLine($"DB 儲存失敗: {ex.Message}"); // 資料庫寫入失敗時輸出錯誤
        }
    }
}
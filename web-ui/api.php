<?php
header('Content-Type: application/json');
$host = "192.168.6.35";
$dbname = "esp32dht22";
$user = "postgres";
$password = "postgres";

try {
    $db = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);

    // 1. 取得最新狀態
    $status_res = $db->query("SELECT payload FROM mqtt_messages WHERE topic='/esp32/status' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    // 2. 取得最新溫度與時間 (作為主時間基準)
    $temp_res = $db->query("SELECT payload, received_at FROM mqtt_messages WHERE topic='/esp32/dht22/temperature' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    // 3. 取得最新濕度
    $humi_res = $db->query("SELECT payload FROM mqtt_messages WHERE topic='/esp32/dht22/humidity' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    // 格式化時間：chart_time 用於圖表標籤(秒), full_time 用於介面顯示
    $raw_time = $temp_res['received_at'] ?? null;
    $chart_time = $raw_time ? date("H:i:s", strtotime($raw_time)) : "--:--:--";
    $full_time = $raw_time ? date("Y-m-d H:i:s", strtotime($raw_time)) : "無資料";

    echo json_encode([
        "status"    => $status_res['payload'] ?? "離線",
        "temp"      => $temp_res['payload'] ?? "--",
        "humi"      => $humi_res['payload'] ?? "--",
        "chart_time" => $chart_time,
        "full_time"  => $full_time
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
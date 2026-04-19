#include <WiFi.h>
#include <WiFiClient.h>
#include <PubSubClient.h>
#include <DHT.h>

// ===== WiFi =====
const char* ssid = "wifi";
const char* password = "king676037";

// ===== MQTT =====
const char* mqtt_server = "192.168.6.35";
const int mqtt_port = 1883;
const char* client_id = "ESP666";

// 如果你的 broker 沒有帳密，可留空 ""
const char* MQTT_USER = "";
const char* MQTT_PASSWORD = "";

// ===== Topics =====
const char* temp_topic      = "/esp32/dht22/temperature";
const char* hum_topic       = "/esp32/dht22/humidity";
const char* status_topic    = "/esp32/status";
const char* heartbeat_topic = "/esp32/heartbeat";

// ===== DHT =====
#define DHTPIN 4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

// ===== MQTT =====
WiFiClient espClient;
PubSubClient client(espClient);

// ===== timer =====
unsigned long lastSensor = 0;
unsigned long lastHeartbeat = 0;

const int sensorInterval = 1000;
const int heartbeatInterval = 1000;

void reconnect() {
  while (!client.connected()) {
    Serial.print("MQTT connecting... ");

    bool ok;

    if (strlen(MQTT_USER) > 0) {
      ok = client.connect(
        client_id,
        MQTT_USER,
        MQTT_PASSWORD,
        status_topic,
        0,
        true,
        "離線"
      );
    } else {
      ok = client.connect(
        client_id,
        status_topic,
        0,
        true,
        "離線"
      );
    }

    if (ok) {
      Serial.println("OK");
      client.publish(status_topic, "在線", true);
    } else {
      Serial.print("failed, rc=");
      Serial.println(client.state());
      delay(3000);
    }
  }
}

void setup() {
  Serial.begin(115200);
  dht.begin();

  WiFi.begin(ssid, password);
  Serial.print("Connecting WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.println("WiFi connected");

  client.setKeepAlive(10);
  client.setServer(mqtt_server, mqtt_port);
}

void loop() {
  if (!client.connected()) reconnect();
  client.loop();

  unsigned long now = millis();

  // ===== heartbeat =====
  if (now - lastHeartbeat > heartbeatInterval) {
    lastHeartbeat = now;

    char msg[20];
    sprintf(msg, "%lu", now / 1000);
    client.publish(heartbeat_topic, msg);
  }

  // ===== sensor =====
  if (now - lastSensor > sensorInterval) {
    lastSensor = now;

    float t = dht.readTemperature();
    float h = dht.readHumidity();

    if (!isnan(t) && !isnan(h)) {
      char tbuf[10], hbuf[10];

      dtostrf(t, 1, 2, tbuf);
      dtostrf(h, 1, 2, hbuf);

      client.publish(temp_topic, tbuf, true);
      client.publish(hum_topic, hbuf, true);
    }
  }
}
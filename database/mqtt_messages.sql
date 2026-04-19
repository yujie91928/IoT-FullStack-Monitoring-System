-- Adminer 5.4.2 PostgreSQL 18.3 dump

DROP TABLE IF EXISTS "mqtt_messages";
DROP SEQUENCE IF EXISTS "public".mqtt_messages_id_seq;
CREATE SEQUENCE "public".mqtt_messages_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."mqtt_messages" (
    "id" integer DEFAULT nextval('mqtt_messages_id_seq') NOT NULL,
    "topic" text NOT NULL,
    "payload" text,
    "received_at" timestamptz DEFAULT now() NOT NULL,
    CONSTRAINT "mqtt_messages_pkey" PRIMARY KEY ("id")
)
WITH (oids = false);


-- 2026-04-19 13:50:49 UTC

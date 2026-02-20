-- Data export for Sinyal Saham Indo (SQLite -> MySQL)
-- Import after running php artisan migrate --force
SET FOREIGN_KEY_CHECKS=0;

-- Table: tiers
DELETE FROM `tiers`;
INSERT INTO `tiers` (`id`,`name`,`min_capital`,`max_capital`,`description`,`created_at`,`updated_at`) VALUES (1,'Starter',0,9999999,'Tier awal untuk modal kecil.','2026-02-20 06:57:31','2026-02-20 06:57:31');
INSERT INTO `tiers` (`id`,`name`,`min_capital`,`max_capital`,`description`,`created_at`,`updated_at`) VALUES (2,'Growth',10000000,49999999,'Tier menengah dengan akses sinyal lebih luas.','2026-02-20 06:57:31','2026-02-20 06:57:31');
INSERT INTO `tiers` (`id`,`name`,`min_capital`,`max_capital`,`description`,`created_at`,`updated_at`) VALUES (3,'Priority',50000000,NULL,'Tier prioritas untuk modal besar.','2026-02-20 06:57:31','2026-02-20 06:57:31');

-- Table: users
DELETE FROM `users`;
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`,`role`,`tier_id`,`address`,`whatsapp_number`,`birth_date`,`religion`,`capital_amount`,`is_active`,`fcm_token`) VALUES (1,'Super Admin','admin@sinyalsahamindo.local',NULL,'$2y$12$.DKKWQuhhqjVTbBuMoZmW.ATo4nbj27.lVnZzV4oo3STQ.f8dYqWq',NULL,'2026-02-20 06:57:31','2026-02-20 06:57:31','admin',NULL,'Jakarta','628123456789','1990-01-01 00:00:00','islam',0,1,NULL);
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`,`role`,`tier_id`,`address`,`whatsapp_number`,`birth_date`,`religion`,`capital_amount`,`is_active`,`fcm_token`) VALUES (2,'Client Demo','client@sinyalsahamindo.local',NULL,'$2y$12$42AMOkxiRBl4HX.rxm/VW.cPDl2VsFrmKhvEJZawWewELK2InpxT2',NULL,'2026-02-20 06:57:31','2026-02-20 10:07:50','client',1,'Bandung','628111111111','1995-06-15 00:00:00','islam',5000000,1,'eTlhtyBUT56CIsd2DkyTl9:APA91bGM4rXjhZ2tdlVa_0uUTWwbERq8ot3J0R9aZialzaE56940zYX_yTJDRK-CCrrGXpqz5IJNLVV_dvj9t-2TWlkz4ebanV2EDzsWWbkpoE3tAy3tVYo');

-- Table: signals
DELETE FROM `signals`;
INSERT INTO `signals` (`id`,`created_by`,`title`,`stock_code`,`signal_type`,`entry_price`,`take_profit`,`stop_loss`,`note`,`published_at`,`created_at`,`updated_at`,`expires_at`) VALUES (1,1,'Breakout Potensi Naik','BBRI','buy',5100,5350,4980,'Volume naik dan konfirmasi resistance.','2026-02-20 09:56:12','2026-02-20 09:56:12','2026-02-20 09:56:12',NULL);
INSERT INTO `signals` (`id`,`created_by`,`title`,`stock_code`,`signal_type`,`entry_price`,`take_profit`,`stop_loss`,`note`,`published_at`,`created_at`,`updated_at`,`expires_at`) VALUES (2,1,'ngebreak','bbri','sell',5900,5500,6000,NULL,'2026-02-20 17:42:00','2026-02-20 10:41:47','2026-02-20 10:43:41','2026-02-20 19:45:00');

-- Table: signal_tier
DELETE FROM `signal_tier`;
INSERT INTO `signal_tier` (`id`,`signal_id`,`tier_id`,`created_at`,`updated_at`) VALUES (1,1,1,NULL,NULL);
INSERT INTO `signal_tier` (`id`,`signal_id`,`tier_id`,`created_at`,`updated_at`) VALUES (2,2,1,NULL,NULL);
INSERT INTO `signal_tier` (`id`,`signal_id`,`tier_id`,`created_at`,`updated_at`) VALUES (3,2,2,NULL,NULL);
INSERT INTO `signal_tier` (`id`,`signal_id`,`tier_id`,`created_at`,`updated_at`) VALUES (4,2,3,NULL,NULL);

-- Table: message_templates
DELETE FROM `message_templates`;
INSERT INTO `message_templates` (`id`,`name`,`event_type`,`religion`,`content`,`is_active`,`created_at`,`updated_at`) VALUES (1,'Ucapan Ulang Tahun','birthday',NULL,'Selamat ulang tahun {name}. Semoga sehat, berkah, dan sukses selalu. Tim Sinyal Saham Indo.',1,'2026-02-20 06:57:31','2026-02-20 17:50:15');
INSERT INTO `message_templates` (`id`,`name`,`event_type`,`religion`,`content`,`is_active`,`created_at`,`updated_at`) VALUES (2,'Ucapan Idul Fitri','holiday','islam','Selamat Hari Raya Idul Fitri {name}. Mohon maaf lahir dan batin. Salam hangat dari Sinyal Saham Indo.',1,'2026-02-20 06:57:31','2026-02-20 06:57:31');

-- Table: wa_blast_logs
DELETE FROM `wa_blast_logs`;
INSERT INTO `wa_blast_logs` (`id`,`admin_id`,`message_template_id`,`blast_type`,`filters`,`recipients_count`,`rendered_messages`,`status`,`blasted_at`,`created_at`,`updated_at`) VALUES (1,1,1,'general','{"source":"manual-test"}',1,'[{"whatsapp_number":"628995235298","message":"Test manual via tinker"}]','sent','2026-02-20 07:44:45','2026-02-20 07:44:45','2026-02-20 07:44:45');
INSERT INTO `wa_blast_logs` (`id`,`admin_id`,`message_template_id`,`blast_type`,`filters`,`recipients_count`,`rendered_messages`,`status`,`blasted_at`,`created_at`,`updated_at`) VALUES (2,1,1,'birthday','{"tier_id":null,"religion":null,"date":"2026-02-20"}',0,'[]','preview',NULL,'2026-02-20 07:46:44','2026-02-20 07:46:44');
INSERT INTO `wa_blast_logs` (`id`,`admin_id`,`message_template_id`,`blast_type`,`filters`,`recipients_count`,`rendered_messages`,`status`,`blasted_at`,`created_at`,`updated_at`) VALUES (3,1,1,'birthday','{"date":"1995-06-15","source":"scheduler"}',1,'[{"name":"Client Demo","whatsapp_number":"628111111111","message":"Selamat ulang tahun Client Demo. Semoga sehat, berkah, dan sukses selalu. Tim Sinyal Saham Indo.","status":"dry-run","response":"Simulasi tanpa kirim."}]','auto-dry-run','2026-02-20 07:50:46','2026-02-20 07:50:46','2026-02-20 07:50:46');
INSERT INTO `wa_blast_logs` (`id`,`admin_id`,`message_template_id`,`blast_type`,`filters`,`recipients_count`,`rendered_messages`,`status`,`blasted_at`,`created_at`,`updated_at`) VALUES (4,1,NULL,'general','{"source":"manual-send"}',1,'[{"name":"Manual","whatsapp_number":"6281901248171","message":"coba wa blast bang,, met ulang tahun yaaaaaaa","status":"sent","response":{"detail":"success! message in queue","id":[144483517],"process":"pending","quota":{"628995295781":{"details":"deduced from total quota","quota":999,"remaining":998,"used":1}},"requestid":389327118,"status":true,"target":["6281901248171"]}}]','manual-sent','2026-02-20 08:00:19','2026-02-20 08:00:19','2026-02-20 08:00:19');

SET FOREIGN_KEY_CHECKS=1;
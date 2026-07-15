USE health_db;

ALTER TABLE notifications
    ADD COLUMN sender_user_id INT NULL AFTER referral_id,
    ADD COLUMN recipient_user_id INT NULL AFTER sender_user_id,
    ADD COLUMN reply_to_notification_id INT NULL AFTER recipient_user_id,
    ADD INDEX idx_notifications_sender_user (sender_user_id),
    ADD INDEX idx_notifications_recipient_user (recipient_user_id),
    ADD INDEX idx_notifications_reply_to (reply_to_notification_id);

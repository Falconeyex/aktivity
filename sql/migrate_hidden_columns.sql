-- Run once on an existing AKTIVITY database.
-- Safe to skip if hidden_columns already exists.

ALTER TABLE user_settings
    ADD COLUMN hidden_columns VARCHAR(255) NOT NULL DEFAULT '[]';

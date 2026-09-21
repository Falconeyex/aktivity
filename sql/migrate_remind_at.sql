-- Run once on an existing AKTIVITY database.
-- Safe to skip if remind_at already exists.

ALTER TABLE cards
    ADD COLUMN remind_at DATETIME NULL DEFAULT NULL;

ALTER TABLE report_reports RENAME COLUMN report_user TO report_actor;
ALTER TABLE report_reports DROP COLUMN report_user_text;

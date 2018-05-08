BEGIN;

CREATE TABLE IF NOT EXISTS /*_*/report_reports (
  -- Primary key
  report_id integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  -- Revision ID
  report_revid integer unsigned NOT NULL,
  -- Timestamp
  report_timestamp varbinary(14) NOT NULL,
  -- Report reason
  report_reason varchar(255) binary NOT NULL DEFAULT '',
  -- Who reported it
  report_user integer unsigned NOT NULL,
  -- Username
  report_user_text varchar(255) binary NOT NULL,
  -- Whether the report has been dealt with
  report_handled boolean NOT NULL DEFAULT 0,
  -- Who dealt with the report
  report_handled_by integer unsigned NOT NULL DEFAULT 0,
  -- Username of handler
  report_handled_by_text varchar(255) binary NOT NULL DEFAULT '',
  -- Timestamp of handling
  report_handled_timestamp varbinary(14) NOT NULL DEFAULT ''
) /*$wgDBTableOptions*/;

-- indexes
CREATE INDEX /*i*/revid ON /*_*/report_reports (report_revid, report_id, report_user, report_timestamp);
CREATE INDEX /*i*/user ON /*_*/report_reports (report_user, report_id, report_revid, report_timestamp);
CREATE INDEX /*i*/age ON /*_*/report_reports (report_timestamp, report_id, report_user, report_revid);

-- handling indexes
CREATE INDEX /*i*/handled ON /*_*/report_reports (report_handled, report_revid, report_id, report_user, report_timestamp);
CREATE INDEX /*i*/handler ON /*_*/report_reports (report_handled_by, report_revid, report_id, report_user, report_timestamp);
CREATE INDEX /*i*/handled_at ON /*_*/report_reports (report_handled_timestamp, report_revid, report_id, report_user, report_timestamp);

COMMIT;

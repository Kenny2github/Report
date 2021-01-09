<?php

class ReportHooks {

	static function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable( 'report_reports',
			__DIR__ . '/sql/table.sql' );
		return true;
	}

	public static function insertReportLink( $rev, &$links, $oldRev, $user ) {
		if ( $user->isAllowed( 'report' ) && !$user->isBlocked() && !$user->isAllowed('handle-reports') ) {
			$links[] = self::generateReportElement( $rev->getID() );
		}
	}

	protected static function generateReportElement( $id ) {
		global $wgUser;
		$dbr = wfGetDB( DB_REPLICA );
		if ($dbr->selectRow( 'report_reports', [ 'report_id' ], [
			'report_revid' => $id,
			'report_user' => $wgUser->getId()
		], __METHOD__ )) {
			return Html::element( 'span', [ 'class' => 'mw-report-reported' ],
				wfMessage( 'report-reported' )->escaped()
			);
		} else {
			return Html::element( 'a', [
				'class' => 'mw-report-report-link',
				'href' => SpecialPage::getTitleFor( 'Report', $id )->getLocalURL(),
			], wfMessage( 'report-report' )->escaped() );
		}
	}

	public static function reportsAwaitingNotice( &$out, &$skin ) {
		$context = $out->getContext();
		if ( !$context->getUser()->isAllowed( 'handle-reports' ) ) {
			return true;
		}
		$title = $context->getTitle();
		if ( !($title->isSpecial( 'Recentchanges' ) || $title->isSpecial( 'Watchlist' )) ) {
			return true;
		}
		$dbr = wfGetDB( DB_REPLICA );
		if (($count = $dbr->selectRowCount( 'report_reports', '*', [
			'report_handled != 1',
		], __METHOD__)) > 0) {
			$out->prependHtml(Html::rawElement('div', [ 'id' => 'mw-report-reports-awaiting' ],
				wfMessage( 'report-reports-awaiting', Html::rawElement('a', [
				'href' => SpecialPage::getTitleFor( 'HandleReports' )->getLocalURL()
			], wfMessage( 'report-reports-awaiting-linktext', $count )->escaped()), $count )->text()));
			$out->addModules( 'ext.report' );
		}
		return true;
	}

}

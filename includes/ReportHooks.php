<?php
namespace MediaWiki\Extension\Report;

use SpecialPage;
use Html;

class ReportHooks {

	static function onLoadExtensionSchemaUpdates( $updater ) {
		$sql_dir = dirname(__DIR__) . '/sql';
		$updater->addExtensionTable(
			'report_reports',
			$sql_dir . '/table.sql'
		);
		return true;
	}

	public static function insertReportLink( $rev, &$links, $oldRev, $user ) {
		if ( $user->isAllowed( 'report' ) && !$user->isBlocked() && !$user->isAllowed('handle-reports') ) {
			$links[] = self::generateReportElement( $rev->getID(), $user );
		}
	}

	protected static function generateReportElement( $id, $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		if ($dbr->selectRow( 'report_reports', [ 'report_id' ], [
			'report_revid' => $id,
			'report_user' => $user->getId()
		], __METHOD__ )) {
			return Html::element(
				'span', [ 'class' => 'mw-report-reported' ],
				wfMessage( 'report-reported' )->text()
			);
		} else {
			return Html::element(
				'a',
				[
					'class' => 'mw-report-report-link',
					'href' => SpecialPage::getTitleFor( 'Report', $id )->getLocalURL(),
				],
				wfMessage( 'report-report' )->text()
			);
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
			$out->prependHtml(Html::rawElement(
				'div', [ 'id' => 'mw-report-reports-awaiting' ],
				wfMessage( 'report-reports-awaiting' )->rawParams(Html::rawElement(
					'a',
					[ 'href' => SpecialPage::getTitleFor( 'HandleReports' )->getLocalURL() ],
					wfMessage( 'report-reports-awaiting-linktext', $count )->parse()
				))->params($count)->parse()
			));
			$out->addModules( 'ext.report' );
		}
		return true;
	}

}

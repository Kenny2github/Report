<?php
namespace MediaWiki\Extension\Report;

use SpecialPage;
use Html;
use MediaWiki\MediaWikiServices;

class SpecialHandleReports extends SpecialPage {

	public function __construct() {
		parent::__construct( 'HandleReports', 'handle-reports' );
	}

	public function execute( $par ) {
		global $wgScriptPath;
		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.report' );
		$out->setPageTitle( wfMessage('report-handling-title')->escaped() );
		$out->setIndexPolicy( 'noindex' );
		$this->checkReadOnly();
		$user = $this->getUser();
		if ( !$this->userCanExecute( $user ) ) {
			$this->displayRestrictionError();
			return;
		}
		$dbr = wfGetDB( DB_REPLICA );
		if (!ctype_digit( $par )) {
			$handled = (strtolower( wfMessage( 'report-handled' )->text() )
				=== strtolower( $par ));
			$this->reportList( $handled, $out );
		} else {
			$this->showReport( $par, $out, $user );
		}
	}

	public function reportList( bool $handled, $out ) {
		if ( $handled ) {
			$subpage = false;
			$key = 'report-handling-view-nothandled';
			$conds = [ 'report_handled' => 1 ];
		} else {
			$subpage = wfMessage( 'report-handled' )->text();
			$key = 'report-handling-view-handled';
			$conds = [ 'report_handled != 1' ];
		}

		$out->addHtml(Html::rawElement('p', [], Html::element(
			'a',
			[ 'href' => $this->getPageTitle( $subpage )->getLocalURL() ],
			wfMessage( $key )->text()
		)));

		$pager = new HandleReportsPager($conds);

		if ($pager->getNumRows() > 0) {
			$out->addHtml(Html::rawElement('div', [], $pager->getNavigationBar()));

			$out->addHTML(Html::openElement(
				'table',
				[ 'class' => 'mw-report-handling-list', 'width' => '100%' ]
			));
			$columns = [
				'report-handling-th-timestamp',
				'report-handling-th-reason',
				'report-handling-th-user',
				'report-handling-th-revid',
				'report-handling-view-report'
			];
			$out->addHTML(Html::openElement('tr'));
			foreach ($columns as $col) {
				$out->addHTML(Html::element('th', [], wfMessage( $col )->text()));
			}
			$out->addHTML(Html::closeElement('tr'));

			$out->addHTML($pager->getBody());

			$out->addHTML(Html::closeElement('table'));

			$out->addHtml(Html::rawElement('div', [], $pager->getNavigationBar()));
		} else {
			$out->addWikiMsg( 'report-handling-no-reports' );
		}
	}

	public function showReport( $par, $out, $user ) {
		
		$userfactory = MediaWikiServices::getInstance()->getUserFactory();

		if ($this->getRequest()->wasPosted()) {
			return $this->onPost( $par, $out, $user );
		}
		$dbcols = [
			'report_reason',
			'report_user',
			'report_revid',
			'report_handled',
			'report_handled_by',
			'report_handled_timestamp'
		];
		$tablecols = [
			'report-handling-mark-handled',
			'report-handling-handledq',
			'report-handling-handled-by',
			'report-handling-th-timestamp'
		];
		$dbr = wfGetDB( DB_REPLICA );
		if ($query = $dbr->selectRow(
			'report_reports',
			$dbcols,
			[ 'report_id' => (int)$par ],
			__METHOD__
		)) {
			$subpage = ($query->report_handled ?
				wfMessage( 'report-handled' )->text() :
				false);
			$out->addWikiMsg( 'returnto', '[[' . $this->getPageTitle(
				$subpage )->getPrefixedText() . ']]' );

			// Report reason display
			$out->addHTML(Html::openElement('fieldset'));
			$out->addHTML(Html::element(
				'legend', [],
				wfMessage( 'report-handling-th-reason' )->text()
			));
			$out->addHTML(Html::element(
				'textarea',
				[ 'readonly' => '', 'class' => 'mw-report-handling-textarea' ],
				$query->report_reason
			));
			$user = $userfactory->newFromId($query->report_user);
			$out->addHTML(Html::closeElement('fieldset'));

			// Report information display
			$out->addHTML(Html::openElement('fieldset'));
			$out->addHTML(Html::element(
				'legend', [],
				wfMessage( 'report-handling-info' )->text()
			));
			$out->addHTML(Html::openElement('table', [ 'class' => 'wikitable' ]));
			// username
			$out->addHTML(Html::openElement('tr'));
			$out->addHTML(Html::element(
				'th', [],
				wfMessage( 'report-handling-username' )->text()
			));
			$out->addHTML(Html::rawElement('td', [], Html::element(
				'a',
				[
					'href' => $user->getUserPage()->getLocalURL(),
					'target' => '_new'
				],
				$user->getName()
			)));
			$out->addHTML(Html::closeElement('tr'));
			// revision ID
			$out->addHTML(Html::openElement('tr'));
			$out->addHTML(Html::element(
				'th', [],
				wfMessage( 'report-handling-revid' )->text()
			));
			$out->addHTML(Html::rawElement('td', [], Html::element(
				'a',
				[
					'href' => SpecialPage::getTitleFor( 'Diff', $query->report_revid )
						->getLocalURL(),
					'target' => '_new'
				],
				$query->report_revid
			)));
			$out->addHTML(Html::closeElement('tr'));
			$out->addHTML(Html::closeElement('table'));
			$out->addHTML(Html::closeElement('fieldset'));

			// admin info
			$out->addHTML(Html::openElement('fieldset'));
			$out->addHTML(Html::element(
				'legend', [],
				wfMessage( 'report-handling' )->text()
			));

			$out->addHTML(Html::openElement(
				'table',
				[
					'class' => 'mw-report-handling-view',
					'width' => '100%'
				]
			));

			$out->addHTML(Html::openElement('tr'));
			foreach ($tablecols as $col) {
				$out->addHTML(Html::element('th', [], wfMessage( $col )->text()));
			}
			$out->addHTML(Html::closeElement('tr'));

			$out->addHTML(Html::openElement('tr'));

			// Mark as handled button
			$out->addHTML(Html::openElement('td'));
			$out->addHTML(Html::openElement('form', [ 'method' => 'POST' ]));
			// <input type="hidden" name="handled" value="1" />
			$out->addHTML(Html::hidden('handled', '1'));
			// <input type="hidden" name="token" value="..." />
			$out->addHTML(Html::hidden('token', $user->getEditToken()));
			// <button type="submit">...</button>
			$out->addHTML(Html::element(
				'button', [ 'type' => 'submit' ],
				wfMessage( 'report-handling-mark-handled' )->text()
			));
			$out->addHTML(Html::closeElement('form'));
			$out->addHTML(Html::closeElement('td'));

			// "Handled?"
			$out->addHTML(Html::element(
				'td', [],
				$query->report_handled ?
				wfMessage( 'report-handling-handled' )->text() :
				wfMessage( 'report-handling-nothandled' )->text()
			));

			// Handler
			$out->addHTML(Html::openElement('td'));
			if ($query->report_handled) {
				$handledby = $userfactory->newFromId($query->report_handled_by);
				$out->addHTML(Html::element(
					'a',
					[
						'href' => $handledby->getUserPage()->getLocalURL(),
						'target' => '_new'
					],
					$handledby->getName()
				));
			} else {
				$out->addHTML(Html::element(
					'span', [],
					wfMessage( 'report-handling-nothandled' )->text()
				));
			}
			$out->addHTML(Html::closeElement('td'));

			// Timestamp when handled
			$out->addHTML(Html::element(
				'td', [],
				$query->report_handled ?
				wfTimestamp( TS_ISO_8601, $query->report_handled_timestamp ) :
				wfMessage( 'report-handling-nothandled' )->text()
			));

			$out->addHTML(Html::closeElement('tr'));

			$out->addHTML(Html::closeElement('table'));
		} else {
			$out->addHTML(Html::element(
				'div', [ 'class' => 'error' ],
				wfMessage( 'report-error-invalid-repid', $par )->text()
			));
			$out->addWikiMsg( 'returnto', '[[' . $this->getPageTitle()->getPrefixedText() . ']]' );
		}
	}

	public function onPost( $par, $out, $user ) {
		if ($user->matchEditToken($this->getRequest()->getText( 'token' ))) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->startAtomic(__METHOD__);
			$dbw->update( 'report_reports', [
				'report_handled' => 1,
				'report_handled_by' => $user->getId(),
				'report_handled_by_text' => $user->getName(),
				'report_handled_timestamp' => wfTimestampNow()
			 ], [ 'report_id' => (int)$par ], __METHOD__ );
			$dbw->endAtomic(__METHOD__);
			$out->addWikiMsg( 'report-has-been-handled' );
			$out->addWikiMsg( 'returnto', '[[' . $this->getPageTitle()->getPrefixedText() . ']]' );
		} else {
			$out->addWikiMsg( 'sessionfailure' );
		}
	}

	public function getGroupName() {
		return 'wiki';
	}

}

<?php

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
			if ( strtolower( $par ) !== strtolower( wfMessage( 'report-handled' )->text() ) ) {
				$out->addHTML(Html::rawElement('a',
					[ 'href' => SpecialPage::getTitleFor( 'HandleReports', wfMessage('report-handled')->text() )->getLocalURL() ],
					wfMessage( 'report-handling-view-handled' )->escaped()
				));
			} else {
				$out->addHTML(Html::rawElement('a',
					[ 'href' => SpecialPage::getTitleFor( 'HandleReports' )->getLocalURL() ],
					wfMessage( 'report-handling-view-nothandled' )->escaped()
				));
			}
			$out->addHTML(Html::openElement(
				'table',
				[ 'class' => 'mw-report-handling-list', 'width' => '100%' ]
			));
			$out->addHTML(Html::openElement('tr'));
			$out->addHTML(Html::rawElement(
				'th', [],
				wfMessage( 'report-handling-th-timestamp' )->escaped()
			));
			$out->addHTML(Html::rawElement(
				'th', [],
				wfMessage( 'report-handling-th-reason' )->escaped()
			));
			$out->addHTML(Html::rawElement(
				'th', [],
				wfMessage( 'report-handling-th-user' )->escaped()
			));
			$out->addHTML(Html::rawElement(
				'th', [],
				wfMessage( 'report-handling-th-revid' )->escaped()
			));
			$out->addHTML(Html::rawElement('th', [],
				wfMessage( 'report-handling-view-report' )->escaped()
			));
			$out->addHTML(Html::closeElement('tr'));
			if ( strtolower( $par ) === strtolower( wfMessage( 'report-handled' )->text() ) ) {
				$conds = [ 'report_handled' => 1 ];
			} else {
				$conds = [ 'report_handled != 1' ];
			}
			foreach ($dbr->select( 'report_reports', [
				'report_id',
				'report_reason',
				'report_user',
				'report_revid',
				'report_timestamp',
			], $conds, __METHOD__)
			as $row) {
				$out->addHTML(Html::openElement('tr'));
				$out->addHTML(Html::rawElement('td', [],
					wfTimestamp( TS_ISO_8601, $row->report_timestamp )
				));
				$out->addHTML(Html::rawElement('td', [], Html::rawElement(
					'textarea',
					[ 'readonly' => '',
					'class' => 'mw-report-handling-textarea' ],
					htmlspecialchars($row->report_reason)
				)));
				$user = User::newFromId($row->report_user);
				$out->addHTML(Html::rawElement('td', [], Html::rawElement('a',
					[ 'href' => $user->getUserPage()->getLocalURL() ],
					htmlspecialchars($user->getName())
				)));
				$out->addHTML(Html::rawElement('td', [], Html::rawElement('a',
					[ 'href' => $wgScriptPath . '/index.php?diff=' . $row->report_revid ],
					htmlspecialchars($row->report_revid)
				)));
				$out->addHTML(Html::rawElement('td', [], Html::rawElement('a',
					[ 'href' => SpecialPage::getTitleFor( 'HandleReports', $row->report_id )->getLocalURL() ],
					wfMessage( 'report-handling-view-report' )->escaped()
				)));
				$out->addHTML(Html::closeElement('tr'));
			}
			$out->addHTML(Html::closeElement('table'));
		} else {
			if ($this->getRequest()->wasPosted()) {
				return $this->onPost( $par, $out, $user );
			}
			$dbr = wfGetDB( DB_REPLICA );
			if ($query = $dbr->selectRow( 'report_reports', [
				'report_reason',
				'report_user',
				'report_revid',
				'report_handled',
				'report_handled_by',
				'report_handled_timestamp'
			], [ 'report_id' => (int)$par ],
			__METHOD__)) {
				$out->addHTML(Html::openElement('fieldset'));
				$out->addHTML(Html::rawElement('legend', [],
					wfMessage( 'report-handling-th-reason' )->escaped()
				));
				$out->addHTML(Html::rawElement(
					'textarea',
					[ 'readonly' => '', 'class' => 'mw-report-handling-textarea' ],
					htmlspecialchars($query->report_reason)
				));
				$user = User::newFromId($query->report_user);
				$out->addHTML(Html::closeElement('fieldset'));
				$out->addHTML(Html::openElement('fieldset'));
				$out->addHTML(Html::rawElement('legend', [],
					wfMessage( 'report-handling-info' )->escaped()
				));
				$out->addHTML(Html::rawElement('b', [],
					wfMessage( 'report-handling-username' )->escaped()
				));
				$out->addHTML(Html::rawElement('a',
					[ 'href' => $user->getUserPage()->getLocalURL() ],
					$user->getName()
				));
				$out->addHTML(Html::rawElement('br'));
				$out->addHTML(Html::rawElement('b', [],
					wfMessage( 'report-handling-revid' )->escaped()
				));
				$out->addHTML(Html::rawElement('td', [], Html::rawElement('a',
					[ 'href' => $wgScriptPath . '/index.php?diff=' . $query->report_revid ],
					htmlspecialchars($query->report_revid)
				)));
				$out->addHTML(Html::closeElement('fieldset'));
				$out->addHTML(Html::openElement('fieldset'));
				$out->addHTML(Html::rawElement('legend', [],
					wfMessage( 'report-handling' )->escaped()
				));
				// <table width="100%">
				$out->addHTML(Html::openElement('table', [ 'width' => '100%' ]));
				// <tr>
				$out->addHTML(Html::openElement('tr'));
				// <th>...</th>
				$out->addHTML(Html::rawElement('th', [],
					wfMessage( 'report-handling-mark-handled' )->escaped()
				));
				// <th>...</th>
				$out->addHTML(Html::rawElement('th', [],
					wfMessage( 'report-handling-handledq' )->escaped()
				));
				// <th>...</th>
				$out->addHTML(Html::rawElement('th', [],
					wfMessage( 'report-handling-handled-by' )->escaped()
				));
				// <th>...</th>
				$out->addHTML(Html::rawElement('th', [],
					wfMessage( 'report-handling-th-timestamp' )->escaped()
				));
				// </tr>
				$out->addHTML(Html::closeElement('tr'));
				// <tr>
				$out->addHTML(Html::openElement('tr'));
				// <td>
				$out->addHTML(Html::openElement('td'));
				// <form method="POST">
				$out->addHTML(Html::openElement('form', [ 'method' => 'POST' ]));
				// <input type="hidden" name="handled" value="1" />
				$out->addHTML(Html::rawElement(
					'input',
					[ 'type' => 'hidden', 'name' => 'handled', 'value' => '1' ]
				));
				// <input type="hidden" name="token" value="..." />
				$out->addHTML(Html::rawElement(
					'input',
					[ 'type' => 'hidden', 'name' => 'token', 'value' => $user->getEditToken() ]
				));
				// <input type="submit" value="..." />
				$out->addHTML(Html::rawElement(
					'input',
					[ 'type' => 'submit', 'value' => wfMessage( 'report-handling-mark-handled' )->escaped() ]
				));
				// </form>
				$out->addHTML(Html::closeElement('form'));
				// </td>
				$out->addHTML(Html::closeElement('td'));
				// <td>...</td>
				$msgkey = 'report-handling-' .
					($query->report_handled ?
					'' :
					'not') .
					'handled';
				$out->addHTML(Html::rawElement('td', [],
					$query->report_handled ?
					wfMessage( 'report-handling-handled' )->escaped() :
					wfMessage( 'report-handling-nothandled' )->escaped()
				));
				// <td>
				$out->addHTML(Html::openElement('td'));
				if ($query->report_handled) {
					$handledby = User::newFromId($query->report_handled_by);
					// <a href="...">...</a>
					$out->addHTML(Html::rawElement('a',
						[ 'href' => $handledby->getUserPage()->getLocalURL() ],
						$handledby->getName()
					));
				} else {
					// <span>...</span>
					$out->addHTML(Html::rawElement('span', [],
						wfMessage( 'report-handling-nothandled' )->escaped()
					));
				}
				// </td>
				$out->addHTML(Html::closeElement('td'));
				// <td>...</td>
				$out->addHTML(Html::rawElement('td', [],
					($query->report_handled ?
					wfTimestamp( TS_ISO_8601, $query->report_handled_timestamp ) :
					wfMessage( 'report-handling-nothandled' )->escaped())
				));
				// </tr>
				$out->addHTML(Html::closeElement('tr'));
				// </table>
				$out->addHTML(Html::closeElement('table'));
			} else {
				$out->addHTML(Html::rawElement(
					'div',
					[ 'class' => 'error' ],
					wfMessage( 'report-error-invalid-repid' )->escaped()
				));
			}
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
			$out->addHTML(Html::rawElement('div', [],
				wfMessage( 'report-has-been-handled' )->escaped()
			));
			$out->addWikiMsg( 'returnto', '[[' . SpecialPage::getTitleFor( 'HandleReports' )->getPrefixedText() . ']]' );
		} else {
			$out->addWikiMsg( 'sessionfailure' );
		}
	}

	public function getGroupName() {
		return 'wiki';
	}

}

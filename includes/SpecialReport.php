<?php
namespace MediaWiki\Extension\Report;

use SpecialPage;
use Html;
use Revision;

class SpecialReport extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Report' );
	}

	private function showError( string $key, $par = null ) {
		$out->addHTML(Html::element(
			'p', [ 'class' => 'error' ],
			$par ? wfMessage( $key, $par )->text() :
			wfMessage( $key )->text()
		));
	}

	public function execute( $par ) {
		$user = $this->getUser();
		$out = $this->getOutput();
		$out->setPageTitle( wfMessage( 'report-title' )->escaped() );
		$out->addModules( 'ext.report' );
		$this->checkReadOnly();
		if ( $user->isBlocked() || !$user->isAllowed( 'report' ) ) {
			$this->showError( 'report-error-missing-perms' );
			return;
		}
		if (!ctype_digit( $par )) {
			$this->showError( 'report-error-invalid-revid', $par );
			return;
		}
		$rev = Revision::newFromId( (int)$par );
		if (!$rev) {
			$this->showError( 'report-error-invalid-revid', $par );
			return;
		}
		$dbr = wfGetDB( DB_REPLICA );
		if ($dbr->selectRow( 'report_reports', [ 'report_id' ], [
			'report_revid' => $rev->getId(),
			'report_user' => $user->getId()
		], __METHOD__ )) {
			$out->addWikiMsg( 'report-already-reported' );
			return;
		}
		$request = $this->getRequest();
		if ($request->wasPosted()) {
			return self::onPost( $par, $out, $request, $user );
		}
		$out->setIndexPolicy( 'noindex' );
		$out->addWikiMsg( 'report-intro', $par );
		$out->addHTML(Html::openElement('form', [ 'method' => 'POST' ]));
		$out->addHTML(Html::hidden('revid', $par,));
		$out->addHTML(Html::textarea('reason'));
		$out->addHTML(Html::hidden('token', $user->getEditToken()));
		$out->addHTML(Html::element(
			'button',
			[ 'type' => 'submit' ],
			wfMessage( 'report-submit' )->text()
		));
		$out->addHTML(Html::closeElement( 'form' ));
	}

	static public function onPost( $par, $out, $request, $user ) {
		if (!$user->matchEditToken($request->getText( 'token' ))) {
			$out->addWikiMsg( 'sessionfailure' );
			return;
		}
		if (!$request->getText('reason')) {
			$out->addHTML(Html::element(
				'p', [ 'class' => 'error '],
				wfMessage( 'report-error-missing-reason' )->text()
			));
			return;
		}
		$dbw = wfGetDB( DB_MASTER );
		$dbw->startAtomic(__METHOD__);
		$dbw->insert( 'report_reports', [
			'report_revid' => (int)$par,
			'report_reason' => $request->getText('reason'),
			'report_user' => $user->getId(),
			'report_user_text' => $user->getName(),
			'report_timestamp' => wfTimestampNow()
		], __METHOD__ );
		$dbw->endAtomic(__METHOD__);
		$out->addWikiMsg( 'report-success' );
		$out->addWikiMsg( 'returnto', '[[' . SpecialPage::getTitleFor('Diff', $par)->getPrefixedText() . ']]' );
	}

	public function getGroupName() {
		return 'wiki';
	}

}

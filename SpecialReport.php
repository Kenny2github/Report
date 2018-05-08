<?php

class SpecialReport extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Report' );
	}

	public function execute( $par ) {
		$user = $this->getUser();
		$out = $this->getOutput();
		$out->setPageTitle( wfMessage('report-title')->escaped() );
		if ( !$user->isAllowed( 'report' ) ) {
			$out->addHTML(Html::rawElement(
				'p',
				[ 'class' => 'error' ],
				wfMessage( 'report-error-missing-perms' )->escaped()
			));
			return;
		}
		if ( $user->isBlocked() ) {
			$out->addHTML(Html::rawElement(
				'p', [ 'class' => 'error' ],
				wfMessage( 'report-error-missing-perms' )->escaped()
			));
		}
		if (!ctype_digit( $par )) {
			$out->addHTML(Html::rawElement(
				'p',
				[ 'class' => 'error' ],
				wfMessage( 'report-error-invalid-revid', $par )->escaped()
			));
			return;
		}
		$rev = Revision::newFromId( (int)$par );
		if (!$rev) {
			$out->addHTML(Html::rawElement(
				'p',
				[ 'class' => 'error' ],
				wfMessage( 'report-error-invalid-revid', $par )->escaped()
			));
			return;
		}
		$dbr = wfGetDB( DB_REPLICA );
		if ($dbr->selectRow( 'report_reports', [ 'report_id' ], [
			'report_revid' => $rev->getId(),
			'report_user' => $user->getId()
		], __METHOD__ )) {
			$out->addHTML(Html::rawElement( 'p', [],
				wfMessage( 'report-already-reported' )->escaped()
			));
			return;
		}
		$request = $this->getRequest();
		if ($request->wasPosted()) {
			return self::onPost( $par, $out, $request );
		}
		$out->setIndexPolicy( 'noindex' );
		$out->addHTML(
			Html::rawElement(
				'p',
				[ 'class' => 'mw-report-intro' ],
				wfMessage( 'report-intro' )
					->params( $par )
					->parse()
			)
		);
		$out->addHTML(Html::openElement(
				'form',
				[ 'method' => 'POST' ]
		));
		$out->addHTML(Html::rawElement(
			'input',
			[
				'type' => 'hidden',
				'name' => 'revid',
				'id' => 'mw-report-form-revid',
				'value' => $par
			]
		));
		$out->addHTML(Html::rawElement(
			'textarea',
			[
				'name' => 'reason',
				'id' => 'mw-report-form-reason'
			]
		));
		$out->addHTML(Html::rawElement(
			'input',
			[
				'type' => 'submit',
				'id' => 'mw-report-form-submit'
			]
		));
		$out->addHTML(Html::closeElement( 'form' ));
	}

	static public function onPost( $par, $out, $request ) {
		global $wgUser;
		if (!$request->getText('reason')) {
			$out->addHTML(Html::rawElement(
				'p',
				[ 'class' => 'error '],
				wfMessage( 'report-error-missing-reason' )->escaped()
			));
		} else {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->begin();
			$dbw->insert( 'report_reports', [
				'report_revid' => (int)$par,
				'report_reason' => $request->getText('reason'),
				'report_user' => $wgUser->getId(),
				'report_user_text' => $wgUser->getName(),
				'report_timestamp' => wfTimestampNow()
			], __METHOD__ );
			$dbw->commit();
			$out->addWikiMsg( 'report-success' );
			$out->addWikiMsg( 'returnto', '[[' . Revision::newFromId( (int)$par )->getTitle()->getPrefixedText() . ']]' );
			return;
		}
	}

	public function getGroupName() {
		return 'wiki';
	}

}

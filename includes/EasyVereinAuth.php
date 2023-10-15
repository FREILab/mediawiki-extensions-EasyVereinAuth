<?php
namespace MediaWiki\Extension\EasyVereinAuth;

use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\PluggableAuth\PluggableAuth as IPluggableAuthBase;
use MediaWiki\Extension\PluggableAuth\PluggableAuthLogin;
use MediaWiki\User\UserIdentity;
use MediaWiki\MediaWikiServices;
use User;

class EasyVereinAuth extends IPluggableAuthBase {

	const EMAIL = 'email';
	const PASSWORD_EV = 'ev_password';
	const TWO_FA_KEY = 'ev_2_fa_key';

	const TWO_FA_PHASE_SESSION_KEY = 'EasyVereinAuth2FAPhase';

	public function authenticate(&$id, &$username, &$realname, &$email, &$errorMsg): bool {
		// Initialize singletons
		$config = Config::newInstance();
		$authManager = MediaWikiServices::getInstance()->getAuthManager();
		$extraLoginFields = $authManager->getAuthenticationSessionData(PluggableAuthLogin::EXTRALOGINFIELDS_SESSION_KEY);

		$email = $extraLoginFields[static::EMAIL];
		$password = $extraLoginFields[static::PASSWORD_EV];
		$two_fa_key = $extraLoginFields[static::TWO_FA_KEY];

		// Sanity checks
		if (!isset($email) || $email === '') {
			$errorMsg = 'Username is missing.';
			return false;
		}

		// get $wgEasyVereinAuth_AssociationCode
		$associationCode = $config->get("AssociationCode");

		if ($associationCode === '') {
			$errorMsg = 'Could not log in due to misconfigured wiki settings. Association code is missing.';
			return false;
		}

		// Get user token
		$data = [
			'username' => $associationCode . '_' . $email,
			'password' => $password,
			'forceReload' => true
		];
		if (isset($two_fa_key)) {
			$data['2FA'] = $two_fa_key;
		}
		$data_string = http_build_query($data);
		$ch = curl_init('https://easyverein.com/api/stable/get-token/');
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($content);
		$needs2fa = $json->needs2FA;
		$memberToken = (string) $json->token;
		$memberID = (string) $json->id;

		if ($needs2fa) {
			$authManager->setAuthenticationSessionData(static::TWO_FA_PHASE_SESSION_KEY, true);
			$errorMsg = '2FA required.';
			return false;
		}

		if ($memberToken === '') {
			$errorMsg = 'Invalid email or password';
			return false;
		}

		// Get member name
		$ch = curl_init('https://easyverein.com/api/stable/member/'.$memberID.'?query={contactDetails{name}}');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Token ' . $memberToken 
		]);
		$content = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($content);
		
		$username = (string) $json->contactDetails->name;
		$realname = (string) $json->contactDetails->name;

		if ($memberToken === '') {
			$errorMsg = 'Something went wrong.';
			return false;
		}

		// Check if user already exists; otherwise, leave $id invalid and make a new user
		$user = User::newFromName($username);
		if ( $user !== false && $user->getId() !== 0 ) {
			$id = $user->getId();
		}
		
		return true;
	}

	public function saveExtraAttributes($id): void {
		// do nothing
	}

	public function deauthenticate(UserIdentity &$user): void {
		$user = null;
	}

	public static function getExtraLoginFields(): array {
		$authManager = MediaWikiServices::getInstance()->getAuthManager();
		$two_fa_phase = $authManager->getAuthenticationSessionData(static::TWO_FA_PHASE_SESSION_KEY);

		$fields = [
			static::EMAIL => [
				'type' => 'string',
				'label' => wfMessage( 'authmanager-email-label' ),
				'help' => wfMessage( 'authmanager-email-help' ),
			],
			static::PASSWORD_EV => [
				'type' => 'password',
				'label' => wfMessage( 'userlogin-yourpassword' ),
				'help' => wfMessage( 'authmanager-password-help' ),
				'sensitive' => true,
			]
		];

		if ($two_fa_phase) {
			$fields[static::TWO_FA_KEY] = [
					'type' => 'string',
					'label' => wfMessage( 'easyvereinauth-login-2fa' ),
			];
		}
		return $fields;
	}
}

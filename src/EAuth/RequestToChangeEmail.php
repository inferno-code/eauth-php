<?php declare(strict_types = 1);

namespace EAuth;

use \EAuth\Request;
use \DateTime;

/**
 * Describe the class which provides all features for change email request.
 */
class RequestToChangeEmail extends Request {

	private $user;

	/**
	 * Create change email request object.
	 *
	 * @param mixed $id The unique ID of request.
	 * @param string $email The new email address of user.
	 * @param string $token The token of request.
	 * @param \EAuth\User $user User who want to change email.
	 * @param string $created Date and time of creation of the request.
	 * @param string $exired Expiration date and time.
	 * @param string|null $activated Date and time of activation of the request or null if the request is not activated.
	 */
	public function __construct($id, string $email, string $token, User $user, string $created, string $expired, string $activated = null) {
		parent::__construct($id, $email, $token, $created, $expired, $activated);
		$this->user = $user;
	}

	/**
	 * Get user who want to change email.
	 *
	 * @return \EAuth\User User's account.
	 */
	public function getUser(): User {
		return $this->user;
	}
}

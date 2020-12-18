<?php declare(strict_types = 1);

namespace EAuth;

use \DateTime;

/**
 * Describe the class which provides basic features for user's requests.
 */
class Request {

	private $id;
	private $email;
	private $token;
	private $created;
	private $expired;
	private $activated;

	/**
	 * Create a basic request object.
	 *
	 * @param mixed $id The unique ID of request.
	 * @param string $email The email address of user.
	 * @param string $token The token of request.
	 * @param string $created Date and time of creation of the request.
	 * @param string $exired Expiration date and time.
	 * @param string|null $activated Date and time of activation of the request or null if the request is not activated.
	 */
	public function __construct($id, string $email, string $token, string $created, string $expired, string $activated = null) {
		$this->id = $id;
		$this->email = $email;
		$this->token = $token;
		$this->created = new DateTime($created);
		$this->expired = new DateTime($expired);
		$this->activated = ($activated ? new DateTime($activated) : null);
	}

	/**
	 * Get ID of request.
	 *
	 * @return mixed ID of request.
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Get email of user.
	 *
	 * @return string Email address of user.
	 */
	public function getEmail(): string {
		return $this->email;
	}

	/**
	 * Get token of request.
	 *
	 * @return string Token of request.
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * Get date and time of creation of the request.
	 *
	 * @return \DateTime Date and time of creation of the request.
	 */
	public function getCreated(): DateTime {
		return $this->created;
	}

	/**
	 * Get date and time of expiration of the request.
	 *
	 * @return \DateTime Expiration date and time.
	 */
	public function getExpired(): DateTime {
		return $this->expired;
	}

	/**
	 * Get date and time of activation of the request.
	 *
	 * @return \DateTime|null Date and time of activation of the request or null if the request is not activated.
	 */
	public function getActivated(): ?DateTime {
		return $this->activated;
	}

	/**
	 * Find whether the request is expired or not.
	 *
	 * @return bool True is the request is activated.
	 */
	public function isExpired(): bool {
		return new DateTime() >= $this->getExpired();
	}

	/**
	 * Find whether the request is activated or not.
	 *
	 * @return bool True is the request is activated.
	 */
	public function isActivated(): bool {
		return $this->activated !== null;
	}
}

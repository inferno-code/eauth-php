<?php declare(strict_types = 1);

namespace EmailAuth;

use \DateTime;

use function openssl_random_pseudo_bytes;

/**
 * Describe the class which provides all features for user's account.
 */
class User {

	const SALT_LENGTH = 16;

	private $id;
	private $email;
	private $profile;
	private $locked;
	private $created;

	/**
	 * Create the user's account object.
	 *
	 * @param mixed $id The user's unique ID.
	 * @param string $email The user's email address.
	 * @param string $password The user's password.
	 * @param array $profile The user's profile data (such as name, birthday, gender etc).
	 * @param bool $locked The user's locking flag.
	 * @param string $created Date and time of creation of account.
	 */
	public function __construct($id, string $email, string $password, array $profile, bool $locked, string $created) {
		$this->id = $id;
		$this->email = $email;
		$this->passwordSalt = openssl_random_pseudo_bytes(self::SALT_LENGTH);
		$this->passwordHash = $this->createPasswordHash($password);
		$this->profile = $profile;
		$this->locked = $locked;
		$this->created = new DateTime($created);
	}

	/**
	 * Get unique user's ID.
	 *
	 * @return mixed The user's ID.
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Get unique user's email.
	 *
	 * @return string The user's email.
	 */
	public function getEmail(): string {
		return $this->email;
	}

	/**
	 * Get user's profile data (such as name, birthday, gender etc).
	 *
	 * @return array The user's profile data (such as name, birthday, gender etc).
	 */
	public function getProfile(): array {
		return $this->profile;
	}

	/**
	 * Find whether the user is locked.
	 *
	 * @return bool True if user is locked, false otherwise.
	 */
	public function isLocked(): bool {
		return $this->locked;
	}

	/**
	 * Get date and time of creation of account.
	 *
	 * @return \DateTime Date and time of creation of account.
	 */
	public function getCreated(): DateTime {
		return $this->created;
	}

	/**
	 * Testing the given password for matching.
	 *
	 * @return bool True if the given password is match with user's password, false otherwise.
	 */
	public function isPasswordMatch(string $password): bool {
		return $this->passwordHash === $this->createPasswordHash($password);
	}

	/**
	 * Hash the given password.
	 *
	 * @return string Hash of password.
	 */
	private function createPasswordHash(string $password): string {
		return md5("{$this->passwordSalt}{$password}");
	}
}

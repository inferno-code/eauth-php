<?php declare(strict_types = 1);

namespace EmailAuth;

use \DateTime;

/**
 * Describe the class which provides all features for user's account.
 */
class User {

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
     * @param array $profile The user's profile data (such as name, birthday, gender etc).
     * @param bool $locked The user's locking flag.
     * @param string $created Date and time of creation of account.
     */
    public function __construct($id, string $email, array $profile, bool $locked, string $created) {
        $this->id = $id;
        $this->email = $email;
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
}

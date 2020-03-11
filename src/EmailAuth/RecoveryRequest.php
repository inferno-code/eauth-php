<?php declare(strict_types = 1);

namespace EmailAuth;

use \EmailAuth\BasicRequest;
use \DateTime;

/**
 * Describe the class which provides all features for recovery request.
 */
class RecoveryRequest extends BasicRequest {

    private $user;

    /**
     * Create recovery request object.
     *
     * @param mixed $id The unique ID of request.
     * @param string $email The email address of user.
     * @param string $token The token of request.
     * @param \EmailAuth\User $user The user who make a request.
     * @param string $created Date and time of creation of the request.
     * @param string $exired Expiration date and time.
     * @param string|null $activated Date and time of activation of the request or null if the request is not activated.
     */
    public function __construct($id, string $email, string $token, User $user, string $created, string $expired, string $activated = null) {
        parent::__construct($id, $email, $token, $created, $expired, $activated);
        $this->user = $user;
    }

    /**
     * Get user account.
     *
     * @return \EmailAuth\User The user's account object.
     */
    public function getUser(): ?User {
        return $this->user;
    }
}

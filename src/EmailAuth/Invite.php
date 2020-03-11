<?php declare(strict_types = 1);

namespace EmailAuth;

use \EmailAuth\BasicRequest;
use \EmailAuth\User;
use \DateTime;

/**
 * Describe the class which provides all features for invite.
 */
class Invite extends BasicRequest {

    private $inviter;

    /**
     * Create invite object.
     *
     * @param mixed $id The unique ID of invite.
     * @param string $email The email address of invited user.
     * @param string $token The token of invite.
     * @param \EmailAuth\User $inviter The inviter's account (if exists).
     * @param string $created Date and time of creation of the request.
     * @param string $exired Expiration date and time.
     * @param string|null $activated Date and time of activation of the request or null if the request is not activated.
     */
    public function __construct($id, string $email, string $token, User $inviter = null, string $created, string $expired, string $activated = null) {
        parent::__construct($id, $email, $token, $created, $expired, $activated);
        $this->inviter = $inviter;
    }

    /**
     * Get inviter's account.
     *
     * @return \EmailAuth\User The inviter's account or null if inviter is not exists.
     */
    public function getInviter(): ?User {
        return $this->inviter;
    }
}

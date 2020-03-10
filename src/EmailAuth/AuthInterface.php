<?php declare(strict_types = 1);

namespace EmailAuth;

use \EmailAuth\User;
use \EmailAuth\Invite;
use \EmailAuth\RecoveryRequest;
use \EmailAuth\ChangeEmailRequest;

/**
 * Describe the interface which provides all features and utilities for secure authentication of individual users.
 */
interface AuthInterface {

    /**
     * Generate new invite for unregistered user.
     *
     * @param string $email Email address of invited user.
     * @param \EmailAuth\User $inviter Reference to inviter or null if inviter is not exists.
     *
     * @return \EmailAuth\Invite An instance of new invite.
     *
     * @throws \EmailAuth\Exceptions\UserAlreadyExistsException If an email is already registered in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If inviter's account is locked.
     * @throws \EmailAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function getInvite(string $email, User $inviter = null): Invite;

    /**
     * Register user using invite.
     *
     * @param \EmailAuth\Invite $invite An invite for user.
     * @param array $profile The user's profile data (such as name, birthday, gender etc).
     * @param string $password The user's password.
     *
     * @return \EmailAuth\User An instance of new user's account.
     *
     * @throws \EmailAuth\Exceptions\UserAlreadyExistsException If an email from invite is already registered in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If inviter's account is locked.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function registerUser(Invite $invite, array $profile, string $password): User;

    /**
     * Attempts to sign in a user with their email address and password.
     *
     * @param string $email The user's email address.
     * @param string $password The user's password.
     *
     * @return \EmailAuth\User An instance of user's account.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If an email is not registered in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\PasswordNotMatchException If the user's password doesn't match.
     * @throws \EmailAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function login(string $email, string $password): User;

    /**
     * Attempts to sign in a user with their email address and password.
     *
     * @param \EmailAuth\User $user Current user.
     * @param string $currentPassword The user's current password.
     * @param string $newPassword The user's new password.
     *
     * @return \EmailAuth\User An instance of updated user's account.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If user is not found in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\PasswordNotMatchException If the user's password doesn't match.
     * @throws \EmailAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): User;

    /**
     * Update user's profile.
     *
     * @param \EmailAuth\User $user Current user.
     * @param array $newProfile The user's new profile data (such as name, birthday, gender etc).
     *
     * @return \EmailAuth\User An instance of updated user's account.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If user is not found in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\PasswordNotMatchException If the user's password doesn't match.
     * @throws \EmailAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function changeProfile(User $user, array $newProfile): User;

    /**
     * Create a new access recovery request.
     *
     * @param string $email Email address of user which lose an access.
     *
     * @return \EmailAuth\RecoveryRequest An instance of request.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If an email is not registered in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function getRecoveryRequest(string $email): RecoveryRequest;

    /**
     * Recovery access using a token from the request.
     *
     * @param \EmailAuth\RecoveryRequest $recoveryRequest An instance of request.
     * @param string $newPassword The user's new password.
     *
     * @return \EmailAuth\User An instance of updated user's account.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If an email from request is not found in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function recovery(RecoveryRequest $recoveryRequest, string $newPassword): User;

    /**
     * Get list of all invites created by user.
     *
     * @param \EmailAuth\User $inviter Inviter's account.
     *
     * @return \EmailAuth\Invite[] An array of invites.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If inviter is not found in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function getInvites(User $inviter): array;

    /**
     * Get list of all invited users by inviter's account.
     *
     * @param \EmailAuth\User $inviter Inviter's account.
     *
     * @return \EmailAuth\User[] An array of invited users.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If inviter is not found in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function getInvitedUsers(User $inviter): array;

    /**
     * Get inviter's account.
     *
     * @param \EmailAuth\User $user User's account.
     *
     * @return \EmailAuth\User|null Inviter's account or null if inviter is not exists.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If the user is not found in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function getInviter(User $user): ?User;

    /**
     * Create a new request to changing user's email.
     *
     * @param \EmailAuth\User $user User's account.
     * @param string $newEmail New email address of user.
     *
     * @return \EmailAuth\ChangeEmailRequest An instance of request.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If an email is not registered in storage.
     * @throws \EmailAuth\Exceptions\UserAlreadyExistsException If a new email is already registered in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function getChangeEmailRequest(User $user, string $newEmail): ChangeEmailRequest;

    /**
     * Changing user's email to new confirmed by token from the request.
     *
     * @param \EmailAuth\ChangeEmailRequest $changeEmailRequest An instance of request.
     *
     * @return \EmailAuth\User An instance of updated user's account.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If an user from request is not found in storage.
     * @throws \EmailAuth\Exceptions\UserAlreadyExistsException If a new email is already registered in storage.
     * @throws \EmailAuth\Exceptions\UserLockedException If users's account is locked.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
    public function changeEmail(ChangeEmailRequest $changeEmailRequest): User;

}
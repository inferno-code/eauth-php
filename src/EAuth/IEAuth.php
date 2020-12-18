<?php declare(strict_types = 1);

namespace EAuth;

use \EAuth\User;
use \EAuth\Invite;
use \EAuth\RequestToRecoveryAccess;
use \EAuth\RequestChangeEmail;

/**
 * Describe the interface which provides all features and utilities for secure authentication of individual users.
 */
interface IEAuth {

	/**
	 * Generate new invite for unregistered user.
	 *
	 * @param string $email Email address of invited user.
	 * @param \EAuth\User $inviter Reference to inviter or null if inviter is not exists.
	 *
	 * @return \EAuth\Invite An instance of new invite.
	 *
	 * @throws \EAuth\Exceptions\UserAlreadyExistsException If the email is already registered in storage.
	 * @throws \EAuth\Exceptions\UserLockedException If inviter's account is locked.
	 * @throws \EAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
	 * @throws \EAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the inviter is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getInvite(string $email, User $inviter = null): Invite;

	/**
	 * Register user using invite.
	 *
	 * @param \EAuth\Invite $invite An invite for user.
	 * @param array $profile The user's profile data (such as name, birthday, gender etc).
	 * @param string $password The user's password.
	 *
	 * @return \EAuth\User An instance of new user's account.
	 *
	 * @throws \EAuth\Exceptions\RequestActivatedException If invite is already activated.
	 * @throws \EAuth\Exceptions\RequestExpiredException If invite is expired.
	 * @throws \EAuth\Exceptions\UserLockedException If inviter's account is locked.
	 * @throws \EAuth\Exceptions\UserAlreadyExistsException If the email is already registered in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function registerUser(Invite $invite, array $profile, string $password): User;

	/**
	 * Attempts to sign in a user with their email address and password.
	 *
	 * @param string $email The user's email address.
	 * @param string $password The user's password.
	 *
	 * @return \EAuth\User An instance of user's account.
	 *
	 * @throws \EAuth\Exceptions\UserNotFoundException If an email is not registered in storage.
	 * @throws \EAuth\Exceptions\UserLockedException If users's account is locked.
	 * @throws \EAuth\Exceptions\PasswordNotMatchException If the user's password doesn't match.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function login(string $email, string $password): User;

	/**
	 * Attempts to sign in a user with their email address and password.
	 *
	 * @param \EAuth\User $user Current user.
	 * @param string $currentPassword The user's current password.
	 * @param string $newPassword The user's new password.
	 *
	 * @return \EAuth\User An instance of updated user's account.
	 *
	 * @throws \EAuth\Exceptions\UserLockedException If users's account is locked.
	 * @throws \EAuth\Exceptions\PasswordNotMatchException If the user's password doesn't match.
	 * @throws \EAuth\Exceptions\UserNotFoundException If user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function changePassword(User $user, string $currentPassword, string $newPassword): User;

	/**
	 * Update user's profile.
	 *
	 * @param \EAuth\User $user Current user.
	 * @param array $newProfile The user's new profile data (such as name, birthday, gender etc).
	 *
	 * @return \EAuth\User An instance of updated user's account.
	 *
	 * @throws \EAuth\Exceptions\UserLockedException If users's account is locked.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function changeProfile(User $user, array $newProfile): User;

	/**
	 * Create a new access recovery request.
	 *
	 * @param string $email Email address of user which lose an access.
	 *
	 * @return \EAuth\RequestToRecoveryAccess An instance of request.
	 *
	 * @throws \EAuth\Exceptions\UserLockedException If users's account is locked.
	 * @throws \EAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
	 * @throws \EAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getRequestToRecoveryAccess(string $email): RequestToRecoveryAccess;

	/**
	 * Recovery access using a token from the request.
	 *
	 * @param \EAuth\RequestToRecoveryAccess $requestToRecoveryAccees An instance of request.
	 * @param string $newPassword The user's new password.
	 *
	 * @return \EAuth\User An instance of updated user's account.
	 *
	 * @throws \EAuth\Exceptions\RequestActivatedException If request is already activated.
	 * @throws \EAuth\Exceptions\RequestExpiredException If request is expired.
	 * @throws \EAuth\Exceptions\UserLockedException If users's account is locked.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function recovery(RequestToRecoveryAccess $requestToRecoveryAccees, string $newPassword): User;

	/**
	 * Get list of all invites created by user.
	 *
	 * @param \EAuth\User $inviter Inviter's account.
	 *
	 * @return \EAuth\Invite[] An array of invites.
	 *
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getInvites(User $inviter): array;

	/**
	 * Get list of all invited users by inviter's account.
	 *
	 * @param \EAuth\User $inviter Inviter's account.
	 *
	 * @return \EAuth\User[] An array of invited users.
	 *
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getInvitedUsers(User $inviter): array;

	/**
	 * Get inviter's account.
	 *
	 * @param \EAuth\User $user User's account.
	 *
	 * @return \EAuth\User|null Inviter's account or null if inviter is not exists.
	 *
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getInviter(User $user): ?User;

	/**
	 * Create a new request to changing user's email.
	 *
	 * @param \EAuth\User $user User's account.
	 * @param string $newEmail New email address of user.
	 *
	 * @return \EAuth\RequestToChangeEmail An instance of request.
	 *
	 * @throws \EAuth\Exceptions\UserLockedException If users's account is locked.
	 * @throws \EAuth\Exceptions\UserAlreadyExistsException If a new email is already registered in storage.
	 * @throws \EAuth\Exceptions\TooManyRequestsException If the number of allowed attempts/requests has been exceeded.
	 * @throws \EAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getRequestToChangeEmail(User $user, string $newEmail): RequestToChangeEmail;

	/**
	 * Changing user's email to new confirmed by token from the request.
	 *
	 * @param \EAuth\RequestToChangeEmail $requestToChangeEmail An instance of request.
	 *
	 * @return \EAuth\User An instance of updated user's account.
	 *
	 * @throws \EAuth\Exceptions\RequestActivatedException If request is already activated.
	 * @throws \EAuth\Exceptions\RequestExpiredException If request is expired.
	 * @throws \EAuth\Exceptions\UserLockedException If users's account is locked.
	 * @throws \EAuth\Exceptions\UserAlreadyExistsException If a new email is already registered in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function changeEmail(RequestToChangeEmail $requestToChangeEmail): User;

	/**
	 * Get user's account by given email.
	 *
	 * @param string $email The user's email.
	 *
	 * @return \EAuth\User User's account.
	 *
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getUserByEmail(string $email): User;

	/**
	 * Get user's account by given ID.
	 *
	 * @param mixed $id The user's ID.
	 *
	 * @return \EAuth\User User's account.
	 *
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getUserByID($id): User;

	/**
	 * Get invite by given token.
	 *
	 * @param string $token The unique token.
	 *
	 * @return \EAuth\Invite Invite object.
	 *
	 * @throws \EAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getInviteByToken(string $token): Invite;

	/**
	 * Get request to recovery access by given token.
	 *
	 * @param string $token The unique token.
	 *
	 * @return \EAuth\RequestToRecoveryAccess An instance of request to recovery access.
	 *
	 * @throws \EAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getRequestToRecoveryAccessByToken(string $token): RequestToRecoveryAccess;

	/**
	 * Get request to change email by given token.
	 *
	 * @param string $token The unique token.
	 *
	 * @return \EAuth\RequestToChangeEmail An instance of request to Change email.
	 *
	 * @throws \EAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
	 * @throws \EAuth\Exceptions\UserNotFoundException If the user is not found in storage.
	 * @throws \EAuth\Exceptions\IOException If storage is not accessible.
	 */
	public function getRequestToChangeEmailByToken(string $token): RequestToChangeEmail;

}
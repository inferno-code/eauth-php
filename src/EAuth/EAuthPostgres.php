<?php declare(strict_types = 1);

namespace EAuth;

use \EAuth\IEAuth;
use \EAuth\User;
use \EAuth\Invite;
use \EAuth\RequestToRecoveryAccess;
use \EAuth\RequestToChangeEmail;
use \EAuth\Exceptions\IOException;
use \EAuth\Exceptions\PasswordNotMatchException;
use \EAuth\Exceptions\TooManyRequestsException;
use \EAuth\Exceptions\UserAlreadyExistsException;
use \EAuth\Exceptions\UserLockedException;
use \EAuth\Exceptions\UserNotFoundException;
use \EAuth\Exceptions\TokenNotFoundException;
use \EAuth\Exceptions\RequestExpiredException;
use \EAuth\Exceptions\RequestActivatedException;
use \EAuth\ITokenGenerator;
use \EAuth\BasicTokenGenerator;
use \Doctrine\DBAL\Connection;
use \Exception;

use function json_encode;
use function json_decode;
use function strtolower;
use function trim;

/**
 *
 */
class EAuthPostgres implements IEAuth {

	private $connection;
	private $tokenGen;
	private $limits;

	/**
	 *
	 */
	public function __construct(Connection $connection, ITokenGenerator $tokenGen, array $limits = []) {

		$this->connection = $connection;
		$this->tokenGen = $tokenGen;
		$this->limits = (object) ($limits + [
			'numInvitesPerDay' => 5,
			'numRecoveryRequestsPerDay' => 5,
			'numChangeEmailRequestsPerDay' => 5,
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getInvite(string $email, User $inviter = null): Invite {

		$email = strtolower(trim($email));

		try {
			$user = $this->getUserByEmail($email);
		} catch (Exception $ex) {
			$user = null;
		}

		if ($user !== null) {
			throw new UserAlreadyExistsException();
		}

		if ($inviter !== null && $inviter->isLocked()) {
			throw new UserLockedException();
		}

		try {

			$invite = $this->connection->transactional(function () use ($email, $inviter) {

				$numAttempts = $this->connection->fetchColumn(
					'
					select count(*) from eauth.invites where email = :email and now() < expired and activated is null
					',
					[
						'email' => $email,
					]
				);

				if ($numAttempts >= $this->limits->numInvitesPerDay) {
					throw new TooManyRequestsException();
				}

				for (;;) {
					$token = $this->tokenGen->generateToken();
					$id = $this->connection->fetchColumn(
						'
						select id from eauth.invites where token = :token limit 1
						',
						[
							'token' => $token
						]
					);
					if (!$id) {
						break;
					}
				}

				$this->connection->executeQuery(
					'
					insert into eauth.invites (token, email, inviter_user_id)
						values (:token, :email, :inviterID)
					',
					[
						'token' => $token,
						'email' => $email,
						'inviterID' => ($inviter !== null ? $inviter->getID() : null)
					]
				);

				return $this->getInviteByToken($token);

			});

			return $invite;

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerUser(Invite $invite, array $profile, string $password): User {

		if ($invite->isActivated()) {
			throw new RequestActivatedException();
		}

		if ($invite->isExpired()) {
			throw new RequestExpiredException();
		}

		$inviter = $invite->getInviter();
		if ($inviter !== null && $inviter->isLocked()) {
			throw new UserLockedException();
		}

		try {
			$user = $this->getUserByEmail($invite->getEmail());
		} catch (Exception $ex) {
			$user = null;
		}

		if ($user !== null) {
			throw new UserAlreadyExistsException();
		}

		try {

			$this->connection->transactional(function () use ($invite, $profile, $password) {

				$this->connection->executeQuery(
					'
					insert into eauth.users (email, invite_id, profile, password)
						values (:email, :inviteID, :profile, :password)
					',
					[
						'email' => $invite->getEmail(),
						'inviteID' => $invite->getID(),
						'profile' => json_encode($profile),
						'password' => $password,
					]
				);

				$this->connection->executeQuery(
					'
					update eauth.invites set activated = now() where id = :id
					',
					[
						'id' => $invite->getID(),
					]
				);

			});

			return $this->getUserByEmail($invite->getEmail());

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		throw new IOException('This method is not implemented yet.');
	}

	/**
	 * {@inheritdoc}
	 */
	public function login(string $email, string $password): User {

		$user = $this->getUserByEmail($email);

		if ($user->isLocked()) {
			throw new UserLockedException();
		}

		if (!$user->isPasswordMatch($password)) {
			throw new PasswordNotMatchException();
		}

		return $user;
	}

	/**
	 * {@inheritdoc}
	 */
	public function changePassword(User $user, string $currentPassword, string $newPassword): User {

		if ($user->isLocked()) {
			throw new UserLockedException();
		}

		if (!$user->isPasswordMatch($currentPassword)) {
			throw new PasswordNotMatchException();
		}

		try {

			$this->connection->executeQuery(
				'
				update eauth.users set password = :newPassword where id = :id
				',
				[
					'newPassword' => $newPassword,
					'id' => $user->getID(),
				]
			);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		return $this->getUserByID($user->getID());
	}

	/**
	 * {@inheritdoc}
	 */
	public function changeProfile(User $user, array $newProfile): User {

		if ($user->isLocked()) {
			throw new UserLockedException();
		}

		try {

			$this->connection->executeQuery(
				'
				update eauth.users set profile = :newProfile where id = :id
				',
				[
					'newProfile' => json_encode($newProfile),
					'id' => $user->getID(),
				]
			);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		return $this->getUserByID($user->getID());
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestToRecoveryAccess(string $email): RequestToRecoveryAccess {

		$user = $this->getUserByEmail($email);

		if ($user->isLocked()) {
			throw new UserLockedException();
		}

		try {

			$request = $this->connection->transactional(function () use ($user) {

				$numAttempts = $this->connection->fetchColumn(
					'
					select count(*) from eauth.requests_to_recovery_access where user_id = :userID and now() < expired and activated is null
					',
					[
						'userID' => $user->getID()
					]
				);

				if ($numAttempts >= $this->limits->numRecoveryRequestsPerDay) {
					throw new TooManyRequestsException();
				}

				for (;;) {
					$token = $this->tokenGen->generateToken();
					$id = $this->connection->fetchColumn(
						'
						select id from eauth.requests_to_recovery_access where token = :token limit 1
						',
						[
							'token' => $token
						]
					);
					if (!$id) {
						break;
					}
				}

				$this->connection->executeQuery(
					'
					insert into eauth.requests_to_recovery_access (token, email, user_id)
						values (:token, :email, :userID)
					',
					[
						'token' => $token,
						'email' => $user->getEmail(),
						'userID' => $user->getID(),
					]
				);

				return $this->getRequestToRecoveryAccessByToken($token);
			});

			return $request;

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function recovery(RequestToRecoveryAccess $requestToRecoveryAccess, string $newPassword): User {

		if ($requestToRecoveryAccess->isActivated()) {
			throw new RequestActivatedException();
		}

		if ($requestToRecoveryAccess->isExpired()) {
			throw new RequestExpiredException();
		}

		if ($requestToRecoveryAccess->getUser()->isLocked()) {
			throw new UserLockedException();
		}

		try {

			$this->connection->transactional(function () use ($requestToRecoveryAccess, $newPassword) {

				$this->connection->executeQuery(
					'
					update eauth.requests_to_recovery_access set activated = now() where id = :id
					',
					[
						'id' => $requestToRecoveryAccess->getID(),
					]
				);

				$this->connection->executeQuery(
					'
					update eauth.users set password = :newPassword where id = :id
					',
					[
						'newPassword' => $newPassword,
						'id' => $requestToRecoveryAccess->getUser()->getID(),
					]
				);

			});

			return $this->getUserByID($requestToRecoveryAccess->getUser()->getID());

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getInvites(User $inviter): array {

		$results = [];

		try {

			$invites = $this->connection->fetchAll(
				'
				select *
				from eauth.invites
				where inviter_user_id = :userID
				',
				[
					'userID' => $inviter->getID(),
				]
			);

			if ($invites) {
				foreach ($invites as $row) {
					$results[] = new Invite($row['id'], $row['email'], $row['token'], $inviter, $row['created'], $row['expired'], $row['activated']);
				}
			}

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		return $results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getInvitedUsers(User $inviter): array {

		$results = [];

		try {

			$users = $this->connection->fetchAll(
				'
				select u.*
				from eauth.invites i, eauth.users u
				where i.inviter_user_id = :userID
					and i.id = u.invite_id
				',
				[
					'userID' => $inviter->getID(),
				]
			);

			if ($users) {
				foreach ($users as $row) {
					$results[] = new User($row['id'], $row['email'], $row['password'], json_decode($row['profile'], true), $row['locked'], $row['created']);
				}
			}

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		return $results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getInviter(User $user): ?User {

		try {

			$inviterID = $this->connection->fetchColumn(
				'
				select i.inviter_user_id
				from eauth.users u, eauth.invites i
				where u.invite_id = i.id
				and u.id = :userID
				',
				[
					'userID' => $user->getID(),
				]
			);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		return ($inviterID ? $this->getUserByID($inviterID) : null);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestToChangeEmail(User $user, string $newEmail): RequestToChangeEmail {

		$newEmail = strtolower(trim($newEmail));

		if ($user->isLocked()) {
			throw new UserLockedException();
		}

		try {
			$anotherUser = $this->getUserByEmail($newEmail);
		} catch (Exception $ex) {
			$anotherUser = null;
		}

		if ($anotherUser !== null) {
			throw UserAlreadyExistsException();
		}

		try {

			$request = $this->connection->transactional(function () use ($user, $newEmail) {

				$numAttempts = $this->connection->fetchColumn(
					'
					select count(*) from eauth.requests_to_change_email where user_id = :userID and now() < expired and activated is null
					',
					[
						'userID' => $user->getID()
					]
				);

				if ($numAttempts >= $this->limits->numChangeEmailRequestsPerDay) {
					throw new TooManyRequestsException();
				}

				for (;;) {
					$token = $this->tokenGen->generateToken();
					$id = $this->connection->fetchColumn(
						'
						select id from eauth.requests_to_change_email where token = :token limit 1
						',
						[
							'token' => $token
						]
					);
					if (!$id) {
						break;
					}
				}

				$this->connection->executeQuery(
					'
					insert into eauth.requests_to_change_email (token, email, user_id)
						values (:token, :newEmail, :userID)
					',
					[
						'token' => $token,
						'newEmail' => $newEmail,
						'userID' => $user->getID(),
					]
				);

				return $this->getRequestToChangeEmailByToken($token);
			});

			return $request;

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function changeEmail(RequestToChangeEmail $requestToChangeEmail): User {

		if ($requestToChangeEmail->isActivated()) {
			throw new RequestActivatedException();
		}

		if ($requestToChangeEmail->isExpired()) {
			throw new RequestExpiredException();
		}

		if ($requestToChangeEmail->getUser()->isLocked()) {
			throw new UserLockedException();
		}

		try {
			$anotherUser = $this->getUserByEmail($requestToChangeEmail->getEmail());
		} catch (Exception $ex) {
			$anotherUser = null;
		}

		if ($anotherUser !== null) {
			throw UserAlreadyExistsException();
		}

		try {

			$this->connection->transactional(function () use ($requestToChangeEmail) {

				$this->connection->executeQuery(
					'
					update eauth.requests_to_change_email set activated = now() where id = :id
					',
					[
						'id' => $requestToChangeEmail->getID(),
					]
				);

				$this->connection->executeQuery(
					'
					update eauth.users set email = :newEmail where id = :id
					',
					[
						'newEmail' => $requestToChangeEmail->getEmail(),
						'id' => $requestToChangeEmail->getUser()->getID(),
					]
				);

			});

			return $this->getUserByID($requestToChangeEmail->getUser()->getID());

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUserByEmail(string $email): User {

		$email = strtolower(trim($email));

		try {

			$row = $this->connection->fetchAssoc(
				'
				select * from eauth.users where email = :email limit 1
				',
				[
					'email' => $email,
				]
			);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		if ($row) {
			return new User($row['id'], $row['email'], $row['password'], json_decode($row['profile'], true), $row['locked'], $row['created']);
		}

		throw new UserNotFoundException();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUserByID($id): User {

		try {

			$row = $this->connection->fetchAssoc(
				'
				select * from eauth.users where id = :id limit 1
				',
				[
					'id' => $id
				]
			);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		if ($row) {
			return new User($row['id'], $row['email'], $row['password'], json_decode($row['profile'], true), $row['locked'], $row['created']);
		}

		throw new UserNotFoundException();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getInviteByToken(string $token): Invite {

		try {

			$row = $this->connection->fetchAssoc(
				'
				select * from eauth.invites where token = :token limit 1
				',
				[
					'token' => $token
				]
			);

		} catch (Exception $ex) {
			throw new IOException($ex->getMessage());
		}

		if ($row) {
			$inviter = null;
			if ($row['inviter_user_id']) {
				$inviter = $this->getUserByID($row['inviter_user_id']);
			}
			return new Invite($row['id'], $row['email'], $row['token'], $inviter, $row['created'], $row['expired'], $row['activated']);
		}

		throw new TokenNotFoundException("Token is not found: {$token}.");
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestToRecoveryAccessByToken(string $token): RequestToRecoveryAccess {

		try {

			$row = $this->connection->fetchAssoc(
				'
				select * from eauth.requests_to_recovery_access where token = :token limit 1
				',
				[
					'token' => $token
				]
			);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		if ($row) {
			$user = $this->getUserByID($row['user_id']);
			return new RequestToRecoveryAccess($row['id'], $row['email'], $row['token'], $user, $row['created'], $row['expired'], $row['activated']);
		}

		throw new TokenNotFoundException("Token is not found: {$token}.");
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestToChangeEmailByToken(string $token): RequestToChangeEmail {

		try {

			$row = $this->connection->fetchAssoc(
				'
				select * from eauth.requests_to_change_email where token = :token limit 1
				',
				[
					'token' => $token
				]
			);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		if ($row) {
			$user = $this->getUserByID($row['user_id']);
			return new RequestToChangeEmail($row['id'], $row['email'], $row['token'], $user, $row['created'], $row['expired'], $row['activated']);
		}

		throw new TokenNotFoundException("Token is not found: {$token}.");
	}
}

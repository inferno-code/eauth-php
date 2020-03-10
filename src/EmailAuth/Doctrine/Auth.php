<?php declare(strict_types = 1);

namespace EmailAuth\Doctrine;

use \EmailAuth\AuthInterface;
use \EmailAuth\User;
use \EmailAuth\Invite;
use \EmailAuth\RecoveryRequest;
use \EmailAuth\ChangeEmailRequest;
use \EmailAuth\Exceptions\IOException;
use \EmailAuth\Exceptions\PasswordNotMatchException;
use \EmailAuth\Exceptions\TooManyRequestsException;
use \EmailAuth\Exceptions\UserAlreadyExistsException;
use \EmailAuth\Exceptions\UserLockedException;
use \EmailAuth\Exceptions\UserNotFoundException;
use \EmailAuth\Exceptions\TokenNotFoundException;
use \EmailAuth\TokenGeneratorInterface;
use \EmailAuth\BasicTokenGenerator;
use \Doctrine\DBAL\Connection;
use \Exception;

use function json_encode;
use function json_decode;

/**
 *
 */
class Auth implements AuthInterface {

	private $connection;
	private $tokenGen;

	/**
	 *
	 */
	public function __construct(Connection $connection, TokenGeneratorInterface $tokenGen) {
		$this->connection = $connection;
		$this->tokenGen = $tokenGen;
	}

    /**
     * {@inheritdoc}
     */
    public function getInvite(string $email, User $inviter = null): Invite {

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

			$this->connection->executeQuery(
				'
				start transaction
				'
			);

			$numAttempts = $this->connection->fetchColumn(
				'
				select count(*) from auth.invites where email = :email and now() < expired and activated is null
				',
				[
					'email' => $email
				]
			);

			if ($numAttempts >= 5) { // TODO add this parameter to cofiguration
				throw new TooManyRequestsException();
			}

			for (;;) {
				$token = $this->tokenGen->generateToken();
				$id = $this->connection->fetchColumn(
					'
					select id from auth.invites where token = :token limit 1
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
				insert into auth.invites (token, email, inviter_user_id)
					values (:token, :email, :inviter_user_id)
				',
				[
					'token' => $token,
					'email' => $email,
					'inviter_user_id' => ($inviter !== null ? $inviter->getID() : null)
				]
			);

			$this->connection->executeQuery(
				'
				commit
				'
			);

			return $this->getInviteByToken($token);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}
	}

    /**
     * {@inheritdoc}
     */
    public function registerUser(Invite $invite, array $profile, string $password): User {

		if ($invite->isActivated()) {
			throw new UserAlreadyExistsException();
		}

		$inviter = $invite->getInviter();
		if ($inviter !== null && $inviter->isLocked()) {
			throw new UserLockedException();
		}

		$email = $invite->getEmail();
		try {
			$user = $this->getUserByEmail($email);
		} catch (Exception $ex) {
			$user = null;
		}

		try {

			$this->connection->executeQuery(
				'
				start transaction
				'
			);

			$this->connection->executeQuery(
				'
				insert into auth.users (email, invite_id, profile, password)
					values (:email, :invite_id, :profile, :password)
				',
				[
					'email' => $email,
					'invite_id' => $invite->getID(),
					'profile' => json_encode($profile),
					'password' => $password,
				]
			);

			$this->connection->executeQuery(
				'
				update auth.invites set activated = now() where id = :id
				',
				[
					'id' => $invite->getID(),
				]
			);

			$this->connection->executeQuery(
				'
				commit
				'
			);

			return $this->getUserByEmail($email);

		} catch (Exception $ex) {

			throw new IOException($ex->getMessage());
		}

		throw new IOException('This method is not implemented yet.');
	}

    /**
     * {@inheritdoc}
     */
    public function login(string $email, string $password): User {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): User {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function changeProfile(User $user, array $newProfile): User {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function getRecoveryRequest(string $email): RecoveryRequest {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function recovery(RecoveryRequest $recoveryRequest, string $newPassword): User {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function getInvites(User $inviter): array {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function getInvitedUsers(User $inviter): array {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function getInviter(User $user): ?User {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function getChangeEmailRequest(User $user, string $newEmail): ChangeEmailRequest {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * {@inheritdoc}
     */
    public function changeEmail(ChangeEmailRequest $changeEmailRequest): User {
		throw new IOException('This method is not implemented yet.'); // TODO
	}

    /**
     * Get user's account by email.
     *
     * @param string $email The user's email.
     *
     * @return \EmailAuth\User User's account.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If the user is not found in storage.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
	public function getUserByEmail(string $email): User {
		try {
			$row = $this->connection->fetchAssoc(
				'
				select * from auth.users where email = :email limit 1
				',
				[
					'email' => $email
				]
			);
		} catch (Exception $ex) {
			throw new IOException($ex->getMessage());
		}
		if ($row) {
			return new User($row['id'], $row['email'], json_decode($row['profile'], true), $row['locked'], $row['created']);
		}
		throw new UserNotFoundException();
	}

    /**
     * Get user's account by ID.
     *
     * @param mixed $id The user's ID.
     *
     * @return \EmailAuth\User User's account.
     *
     * @throws \EmailAuth\Exceptions\UserNotFoundException If the user is not found in storage.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
	public function getUserByID($id): User {
		try {
			$row = $this->connection->fetchAssoc(
				'
				select * from auth.users where id = :id limit 1
				',
				[
					'id' => $id
				]
			);
		} catch (Exception $ex) {
			throw new IOException($ex->getMessage());
		}
		if ($row) {
			return new User($row['id'], $row['email'], json_decode($row['profile'], true), $row['locked'], $row['created']);
		}
		throw new UserNotFoundException();
	}

    /**
     * Get invite by token.
     *
     * @param string $token The unique token.
     *
     * @return \EmailAuth\Invite Invite object.
     *
     * @throws \EmailAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
	public function getInviteByToken(string $token): Invite {
		try {
			$row = $this->connection->fetchAssoc(
				'
				select * from auth.invites where token = :token limit 1
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

}

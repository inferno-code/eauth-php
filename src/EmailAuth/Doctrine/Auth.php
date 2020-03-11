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
use \EmailAuth\Exceptions\RequestExpiredException;
use \EmailAuth\Exceptions\RequestActivatedException;
use \EmailAuth\TokenGeneratorInterface;
use \EmailAuth\BasicTokenGenerator;
use \Doctrine\DBAL\Connection;
use \Exception;

use function json_encode;
use function json_decode;
use function strtolower;
use function trim;

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
					'email' => $email,
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
					values (:token, :email, :inviterID)
				',
				[
					'token' => $token,
					'email' => $email,
					'inviterID' => ($inviter !== null ? $inviter->getID() : null)
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
			throw new RequestActivatedException();
		}

		if ($invite->isExpired()) {
			throw new RequestExpiredException();
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
					values (:email, :inviteID, :profile, :password)
				',
				[
					'email' => $email,
					'inviteID' => $invite->getID(),
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
    			update auth.users set password = :newPassword where id = :id
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
    			update auth.users set profile = :newProfile where id = :id
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
    public function getRecoveryRequest(string $email): RecoveryRequest {

        $user = $this->getUserByEmail($email);

        if ($user->isLocked()) {
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
				select count(*) from auth.recovery_requests where user_id = :userID and now() < expired and activated is null
				',
				[
					'userID' => $user->getID()
				]
			);

			if ($numAttempts >= 5) { // TODO add this parameter to cofiguration
				throw new TooManyRequestsException();
			}

			for (;;) {
				$token = $this->tokenGen->generateToken();
				$id = $this->connection->fetchColumn(
					'
					select id from auth.recovery_requests where token = :token limit 1
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
				insert into auth.recovery_requests (token, email, user_id)
					values (:token, :email, :userID)
				',
				[
					'token' => $token,
					'email' => $email,
					'userID' => $user->getID(),
				]
			);

			$this->connection->executeQuery(
				'
				commit
				'
			);

			return $this->getRecoveryRequestByToken($token);

        } catch (Exception $ex) {

			throw new IOException($ex->getMessage());
        }
	}

    /**
     * {@inheritdoc}
     */
    public function recovery(RecoveryRequest $recoveryRequest, string $newPassword): User {

        if ($recoveryRequest->isActivated()) {
            throw new RequestActivatedException();
        }

        if ($recoveryRequest->isExpired()) {
            throw new RequestExpiredException();
        }

        $user = $recoveryRequest->getUser();
        if ($user->isLocked()) {
            throw new UserLockedException();
        }

        try {

			$this->connection->executeQuery(
				'
				start transaction
				'
			);

    		$this->connection->executeQuery(
    			'
    			update auth.recovery_requests set activated = now() where id = :id
    			',
    			[
    				'id' => $recoveryRequest->getID(),
    			]
    		);

    		$this->connection->executeQuery(
    			'
    			update auth.users set password = :newPassword where id = :id
    			',
    			[
    				'newPassword' => $newPassword,
    				'id' => $user->getID(),
    			]
    		);

			$this->connection->executeQuery(
				'
				commit
				'
			);

            return $this->getUserByID($user->getID());

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
                from auth.invites
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
                from auth.invites i, auth.users u
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
                from auth.users u, auth.invites i
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
    public function getChangeEmailRequest(User $user, string $newEmail): ChangeEmailRequest {

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

			$this->connection->executeQuery(
				'
				start transaction
				'
			);

			$numAttempts = $this->connection->fetchColumn(
				'
				select count(*) from auth.change_email_requests where user_id = :userID and now() < expired and activated is null
				',
				[
					'userID' => $user->getID()
				]
			);

			if ($numAttempts >= 5) { // TODO add this parameter to cofiguration
				throw new TooManyRequestsException();
			}

			for (;;) {
				$token = $this->tokenGen->generateToken();
				$id = $this->connection->fetchColumn(
					'
					select id from auth.change_email_requests where token = :token limit 1
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
				insert into auth.change_email_requests (token, email, user_id)
					values (:token, :newEmail, :userID)
				',
				[
					'token' => $token,
					'newEmail' => $newEmail,
					'userID' => $user->getID(),
				]
			);

			$this->connection->executeQuery(
				'
				commit
				'
			);

			return $this->getChangeEmailRequestByToken($token);

        } catch (Exception $ex) {

			throw new IOException($ex->getMessage());
        }
	}

    /**
     * {@inheritdoc}
     */
    public function changeEmail(ChangeEmailRequest $changeEmailRequest): User {

        if ($changeEmailRequest->isActivated()) {
            throw new RequestActivatedException();
        }

        if ($changeEmailRequest->isExpired()) {
            throw new RequestExpiredException();
        }

        $user = $changeEmailRequest->getUser();
        if ($user->isLocked()) {
            throw new UserLockedException();
        }

        try {
            $anotherUser = $this->getUserByEmail($changeEmailRequest->getEmail());
        } catch (Exception $ex) {
            $anotherUser = null;
        }

        if ($anotherUser !== null) {
            throw UserAlreadyExistsException();
        }

        try {

			$this->connection->executeQuery(
				'
				start transaction
				'
			);

    		$this->connection->executeQuery(
    			'
    			update auth.change_email_requests set activated = now() where id = :id
    			',
    			[
    				'id' => $changeEmailRequest->getID(),
    			]
    		);

    		$this->connection->executeQuery(
    			'
    			update auth.users set email = :newEmail where id = :id
    			',
    			[
    				'newEmail' => $changeEmailRequest->getEmail(),
    				'id' => $user->getID(),
    			]
    		);

			$this->connection->executeQuery(
				'
				commit
				'
			);

            return $this->getUserByID($user->getID());

        } catch (Exception $ex) {

			throw new IOException($ex->getMessage());
        }
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
        $email = strtolower(trim($email));
		try {
			$row = $this->connection->fetchAssoc(
				'
				select * from auth.users where email = :email limit 1
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
			return new User($row['id'], $row['email'], $row['password'], json_decode($row['profile'], true), $row['locked'], $row['created']);
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

    /**
     * Get recovery request by token.
     *
     * @param string $token The unique token.
     *
     * @return \EmailAuth\RecoveryRequest Recovery request object.
     *
     * @throws \EmailAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
	public function getRecoveryRequestByToken(string $token): RecoveryRequest {
		try {
			$row = $this->connection->fetchAssoc(
				'
				select * from auth.recovery_requests where token = :token limit 1
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
			return new RecoveryRequest($row['id'], $row['email'], $row['token'], $user, $row['created'], $row['expired'], $row['activated']);
		}
		throw new TokenNotFoundException("Token is not found: {$token}.");
	}

    /**
     * Get change email request by token.
     *
     * @param string $token The unique token.
     *
     * @return \EmailAuth\ChangeEmailRequest Change email request object.
     *
     * @throws \EmailAuth\Exceptions\TokenNotFoundException If the token is not found in storage.
     * @throws \EmailAuth\Exceptions\IOException If storage is not accessible.
     */
	public function getChangeEmailRequestByToken(string $token): ChangeEmailRequest {
		try {
			$row = $this->connection->fetchAssoc(
				'
				select * from auth.change_email_requests where token = :token limit 1
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
			return new ChangeEmailRequest($row['id'], $row['email'], $row['token'], $user, $row['created'], $row['expired'], $row['activated']);
		}
		throw new TokenNotFoundException("Token is not found: {$token}.");
	}
}

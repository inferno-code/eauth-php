<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use \EmailAuth\Doctrine\Auth;
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

$dbConfig = [
	'host' => '127.0.0.1',
	'port' => '5432',
	'dbname' => 'emailauth',
	'user' => 'postgres',
	'password' => '54321',
	'driver' => 'pdo_pgsql',
];

$connectionConfig = new \Doctrine\DBAL\Configuration();
$conn = \Doctrine\DBAL\DriverManager::getConnection($dbConfig, $connectionConfig);
$conn->query("set client_encoding = 'utf-8'");
/*
var_dump($conn->fetchColumn('select now()'));
*/
/*
var_dump($conn->transactional(function ($conn) {
	$dt = $conn->fetchColumn('select now()');
	return [ 'currentDateAndTime' => $dt ];
}));
*/
/*
$queryBuilder = $conn->createQueryBuilder();
$queryBuilder
	->select('*')
	->from('auth.users')
	->where(
		$queryBuilder->expr()->andX(
			$queryBuilder->expr()->eq('username', ':userID')
		)
	);
var_dump($queryBuilder->getSQL());exit;
*/

$auth = new Auth($conn, new BasicTokenGenerator());

/*
try {
	$invite = $auth->getInvite('test@test');
	var_dump($invite);
} catch (UserAlreadyExistsException $ex) {
	print "User already exists\n";
} catch (UserLockedException $ex) {
	print "Inviter is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n" . $ex->getMessage();
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

/*
try {
	$invite = $auth->getInviteByToken('aa3bf5dfbbb5dd0bca55cd08f4600a0d');
	$profile = [
		'name' => 'John Connor',
		'gender' => 'male',
	];
	$password = 'test';
	$user = $auth->registerUser($invite, $profile, $password);
	var_dump($user);
} catch (TokenNotFoundException $ex) {
	print "Token not found\n";
} catch (RequestExpiredException $ex) {
	print "Request is expired\n";
} catch (RequestActivatedException $ex) {
	print "Request is already activated\n";
} catch (UserNotFoundException $ex) {
	print "User not found\n";
} catch (UserAlreadyExistsException $ex) {
	print "User already exists\n";
} catch (UserLockedException $ex) {
	print "Inviter is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n" . $ex->getMessage();
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

/*
try {
	$user = $auth->getUserByEmail('test@test');
	var_dump($user);
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

/*
try {
	$user = $auth->login('test@test', 'test');
	var_dump($user);
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (PasswordNotMatchException $ex) {
	print "Password is not match\n";
} catch (UserLockedException $ex) {
	print "User is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error: " . $ex->getMessage() . "\n";
}
*/

/*
try {
	$user = $auth->getUserByEmail('test@test');
    $user = $auth->changePassword($user, 'test', 'test1');
	var_dump($user);
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (PasswordNotMatchException $ex) {
	print "Password is not match\n";
} catch (UserLockedException $ex) {
	print "User is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error: " . $ex->getMessage() . "\n";
}
*/

/*
try {
	$user = $auth->getUserByEmail('test@test');
    $user = $auth->changeProfile($user, [
		'name' => 'John Connor',
		'gender' => 'male',
        'birthday' => '1985-02-28'
    ]);
	var_dump($user);
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (PasswordNotMatchException $ex) {
	print "Password is not match\n";
} catch (UserLockedException $ex) {
	print "User is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error: " . $ex->getMessage() . "\n";
}
*/

/*
try {
    $recoveryRequest = $auth->getRecoveryRequest('test@test');
	var_dump($recoveryRequest);
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (UserLockedException $ex) {
	print "User is locked\n";
} catch (TooManyRequestsException $ex) {
	print "Too many requests\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error: " . $ex->getMessage() . "\n";
}
*/

/*
try {
    $recoveryRequest = $auth->getRecoveryRequestByToken('372a004464335233d4a1d01c49d97b6c');
    $user = $auth->recovery($recoveryRequest, 'test');
	var_dump($user);
} catch (RequestExpiredException $ex) {
	print "Request is expired\n";
} catch (RequestActivatedException $ex) {
	print "Request is already activated\n";
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (TooManyRequestsException $ex) {
	print "Too many requests\n";
} catch (PasswordNotMatchException $ex) {
	print "Password is not match\n";
} catch (UserLockedException $ex) {
	print "User is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error: " . $ex->getMessage() . "\n";
}
*/

/*
try {
	$user = $auth->getUserByEmail('test@test');
    $changeEmailRequest = $auth->getChangeEmailRequest($user, 'john@connor.gov');
	var_dump($changeEmailRequest);
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (UserAlreadyExistsException $ex) {
	print "User is already exists\n";
} catch (UserLockedException $ex) {
	print "User is locked\n";
} catch (TooManyRequestsException $ex) {
	print "Too many requests\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error: " . $ex->getMessage() . "\n";
}
*/

/*
try {
    $changeEmailRequest = $auth->getChangeEmailRequestByToken('51df5c903a332dab7743a4f7b12ff17d');
    $user = $auth->changeEmail($changeEmailRequest);
	var_dump($user);
} catch (RequestExpiredException $ex) {
	print "Request is expired\n";
} catch (RequestActivatedException $ex) {
	print "Request is already activated\n";
} catch (UserNotFoundException $ex) {
	print "User is not found\n";
} catch (UserAlreadyExistsException $ex) {
	print "User is already exists\n";
} catch (TooManyRequestsException $ex) {
	print "Too many requests\n";
} catch (PasswordNotMatchException $ex) {
	print "Password is not match\n";
} catch (UserLockedException $ex) {
	print "User is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n";
} catch (Exception $ex) {
	print "Unexpacted error: " . $ex->getMessage() . "\n";
}
*/

/*
try {
    $user = $auth->getUserByEmail('john@connor.gov');
	$invite = $auth->getInvite('sarah@connor.gov', $user);
	var_dump($invite);
} catch (UserAlreadyExistsException $ex) {
	print "User already exists\n";
} catch (TooManyRequestsException $ex) {
	print "Too many requests\n";
} catch (UserLockedException $ex) {
	print "Inviter is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n" . $ex->getMessage();
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

/*
try {
	$invite = $auth->getInviteByToken('80ec62d5c750865ffb111daf1e6effe7');
	$profile = [
		'name' => 'Sarah Connor',
		'gender' => 'female',
	];
	$password = 'test';
	$user = $auth->registerUser($invite, $profile, $password);
	var_dump($user);
} catch (TokenNotFoundException $ex) {
	print "Token not found\n";
} catch (RequestExpiredException $ex) {
	print "Request is expired\n";
} catch (RequestActivatedException $ex) {
	print "Request is already activated\n";
} catch (UserNotFoundException $ex) {
	print "User not found\n";
} catch (UserAlreadyExistsException $ex) {
	print "User already exists\n";
} catch (UserLockedException $ex) {
	print "Inviter is locked\n";
} catch (IOException $ex) {
	print "Storage is not available now\n" . $ex->getMessage();
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

/*
try {
    $user = $auth->getUserByEmail('sarah@connor.gov');
    $inviter = $auth->getInviter($user);
	var_dump($inviter);
    $user = $auth->getUserByEmail('john@connor.gov');
    $inviter = $auth->getInviter($user);
	var_dump($inviter);
} catch (UserNotFoundException $ex) {
	print "User not found\n";
} catch (IOException $ex) {
	print "Storage is not available now\n" . $ex->getMessage();
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

/*
try {
    $user = $auth->getUserByEmail('john@connor.gov');
    $invites = $auth->getInvites($user);
	var_dump($invites);
} catch (UserNotFoundException $ex) {
	print "User not found\n";
} catch (IOException $ex) {
	print "Storage is not available now\n" . $ex->getMessage();
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

/*
try {
    $user = $auth->getUserByEmail('john@connor.gov');
    $invitedUsers = $auth->getInvitedUsers($user);
	var_dump($invitedUsers);
} catch (UserNotFoundException $ex) {
	print "User not found\n";
} catch (IOException $ex) {
	print "Storage is not available now\n" . $ex->getMessage();
} catch (Exception $ex) {
	print "Unexpacted error\n";
}
*/

// ----------------------------------------------------

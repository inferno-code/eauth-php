<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use \EAuth\IEAuth;
use \EAuth\EAuthPostgres;
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

$dbConfig = [
	'host' => '127.0.0.1',
	'port' => '5432',
	'dbname' => 'eauth',
	'user' => 'postgres',
	'password' => '54321',
	'driver' => 'pdo_pgsql',
];

$connectionConfig = new \Doctrine\DBAL\Configuration();
$conn = \Doctrine\DBAL\DriverManager::getConnection($dbConfig, $connectionConfig);
$conn->query("set client_encoding = 'utf-8'");

var_dump($conn->fetchColumn('select now()'));

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

$auth = new EAuthPostgres($conn, new BasicTokenGenerator());

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
    $requestToRecoveryAccess = $auth->getRequestToRecoveryAccess('test@test');
	var_dump($requestToRecoveryAccess);
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
    $requestToRecoveryAccess = $auth->getRequestToRecoveryAccessByToken('372a004464335233d4a1d01c49d97b6c');
    $user = $auth->recovery($requestToRecoveryAccess, 'test');
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
    $requestToChangeEmail = $auth->getRequestToChangeEmail($user, 'john@connor.gov');
	var_dump($requestToChangeEmail);
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
    $requestToChangeEmail = $auth->getRequestToChangeEmailByToken('51df5c903a332dab7743a4f7b12ff17d');
    $user = $auth->changeEmail($requestToChangeEmail);
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

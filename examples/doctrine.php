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

$auth = new Auth($conn, new BasicTokenGenerator());

/*
try {
	$invite = $auth->getInvite('test@test'); // TODO test with inviter
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
	$invite = $auth->getInviteByToken('bebb34a4edb602d2ddd73b3da2a3437c');
	$profile = [
		'name' => 'John Conor',
		'gender' => 'male',
	];
	$password = 'test';
	$user = $auth->registerUser($invite, $profile, $password);
	var_dump($user);
} catch (TokenNotFoundException $ex) {
	print "Token not found\n";
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

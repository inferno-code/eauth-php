<?php declare(strict_types = 1);

namespace EmailAuth;

/**
 * Describe the interface for token generation and validation.
 */
interface TokenGeneratorInterface {

	/**
	 * Generate new access token.
	 *
	 * @return string Generated access token.
	 */
	public function generateToken(): string;
	
	/**
	 * Find whether the $token is a valid access token.
	 * 
	 * @param string $token Access token.
	 * @return bool Returns true if the token is a valid access token, or false otherwise.
	 */
	public function isValidToken(string $token): bool;
}

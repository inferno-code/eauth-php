<?php declare(strict_types = 1);

namespace EmailAuth;

use \EmailAuth\TokenGeneratorInterface;

use \InvalidArgumentException;

use function bin2hex;
use function openssl_random_pseudo_bytes;
use function preg_match;
use function sprintf;

/**
 * Basic implementation of interface TokenGeneratorInterface.
 */
class BasicTokenGenerator implements TokenGeneratorInterface {
	
	private $length;
	
	/**
	 * Create token generator object.
	 *
	 * @param int $length Length of token.
	 *
	 * @throws \InvalidArgumentException If the length is not a positive number.
	 */
	public function __construct(int $length = 16) {
		if ($length <= 0) {
			 throw new InvalidArgumentException(sprintf(
				"Invalid length of token: %d",
				$length
			));
		}
		$this->length = $length;
	}

	/**
	 * {@inheritdoc}
	 */
	public function generateToken(): string {
		return bin2hex(openssl_random_pseudo_bytes($this->length));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function isValidToken(string $token): bool {
		return preg_match('/^[0-9a-fA-F]{' . ($this->length * 2) . '}$/', $token) ? true : false;
	}
}

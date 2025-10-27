<?php
namespace App\Service;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class JwtToken
{
    private Configuration $config;

    public function __construct(
        private string $jwt_secret_api_key
    )
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->jwt_secret_api_key)
        );
    }

    public function createToken(array $payload = [], int $ttlSeconds = 3600 * 24 * 365): string
    {
        $now = new \DateTimeImmutable();
        $builder = $this->config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify("+{$ttlSeconds} seconds"));

        return $builder->getToken($this->config->signer(), $this->config->signingKey())->toString();
    }

    public function verifyToken(string $jwt): bool
    {
        try {
            $token = $this->config->parser()->parse($jwt);
            assert($token instanceof UnencryptedToken);

            $constraint = new SignedWith($this->config->signer(), $this->config->signingKey());
            return $this->config->validator()->validate($token, $constraint);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

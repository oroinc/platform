<?php

namespace Oro\Bundle\TestFrameworkBundle\Faker;

use Faker\Generator;
use Faker\Provider\Base as BaseProvider;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Faker provider password hashing for users
 */
class UserHashPasswordProvider extends BaseProvider
{
    /**
     * @var PasswordHasherInterface
     */
    private $passwordHasher;

    public function __construct(
        Generator $generator
    ) {
        parent::__construct($generator);
        $this->passwordHasher = new SodiumPasswordHasher();
    }

    public function userPassword(string $plainPassword): string
    {
        return $this->passwordHasher->hash($plainPassword);
    }
}

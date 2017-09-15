<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;

class LoadDuplicateUserData extends AbstractFixture
{
    /** @var array */
    protected static $users = [
        [
            'username' => 'u1',
            'email' => 'u1@example.com',
            'password' => 'u1',
            'first_name' => 'Duplicate',
        ],
        [
            'username' => 'u2',
            'email' => 'u2@example.com',
            'password' => 'u2',
            'first_name' => 'Duplicate',
        ],
        [
            'username' => 'u3',
            'email' => 'u3@example.com',
            'password' => 'u3',
            'first_name' => 'Different',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$users as $data) {
            $user = new User();
            $user->setUsername($data['username'])
                ->setEmail($data['email'])
                ->setPassword($data['password'])
                ->setFirstName($data['first_name']);

            $manager->persist($user);
        }

        $manager->flush();
    }
}

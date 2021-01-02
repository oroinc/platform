<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class LoadUserData extends AbstractFixture
{
    /** @var array */
    protected static $users = [
        [
            'username'         => 'u1',
            'email'            => 'u1@example.com',
            'password'         => 'u1',
            'additional_email' => 'test1@example.com',
            'enabled'          => true
        ],
        [
            'username'         => 'u2',
            'email'            => 'u2@example.com',
            'password'         => 'u2',
            'additional_email' => 'test2@example.com',
            'enabled'          => false
        ],
        [
            'username'         => 'u3',
            'email'            => 'u3@example.com',
            'password'         => 'u3',
            'additional_email' => 'test3@example.com',
            'enabled'          => true
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $createdUsers = [];
        foreach (self::$users as $data) {
            $email = new Email();
            $email->setEmail($data['additional_email']);

            $user = new User();
            $user->setUsername($data['username'])
                ->setEmail($data['email'])
                ->setPassword($data['password'])
                ->addEmail($email)
                ->setEnabled($data['enabled']);

            $manager->persist($email);
            $manager->persist($user);
            $createdUsers[] = $user;
        }

        $manager->flush();

        //update login count for users. This cannot be done during user creation
        //because during creation the login count always resets to 0.
        $loginCount = 0;
        foreach ($createdUsers as $user) {
            $loginCount += 10;
            $user->setLoginCount($loginCount);
        }
        $manager->flush();
    }
}

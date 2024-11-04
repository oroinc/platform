<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Loads the first user from the database.
 * This user is the system administrator.
 */
class LoadUser extends AbstractFixture implements InitialFixtureInterface
{
    public const USER = 'user';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)
            ->createQueryBuilder('t')
            ->orderBy('t.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
        $this->addReference(self::USER, $user);
    }
}

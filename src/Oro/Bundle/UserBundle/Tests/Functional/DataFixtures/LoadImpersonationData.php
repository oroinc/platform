<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;

class LoadImpersonationData extends AbstractFixture implements DependentFixtureInterface
{
    public const IMPERSONATION_SIMPLE_USER = 'impersonation_simple_user';
    public const IMPERSONATION_SIMPLE_USER_EXPIRED = 'impersonation_simple_user_expired';

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [LoadUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);

        $impersonation = new Impersonation();
        $impersonation->setUser($simpleUser);
        $impersonation->getExpireAt()->add(\DateInterval::createFromDateString('+1 day'));
        $impersonation->setNotify(true);
        $manager->persist($impersonation);

        $this->setReference(self::IMPERSONATION_SIMPLE_USER, $impersonation);

        $impersonation = new Impersonation();
        $impersonation->setUser($simpleUser);
        $impersonation->getExpireAt()->add(\DateInterval::createFromDateString('-1 day'));
        $impersonation->setNotify(true);
        $manager->persist($impersonation);

        $this->setReference(self::IMPERSONATION_SIMPLE_USER_EXPIRED, $impersonation);

        $manager->flush();
    }
}

<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class LoadScopeDataForScopeManagerTests extends AbstractFixture implements DependentFixtureInterface
{
    public const ORGANIZATION            = 'organization';
    public const USER                    = 'user';
    public const USER1                   = LoadUserData::SIMPLE_USER;
    public const USER2                   = LoadUserData::SIMPLE_USER_2;
    public const DEFAULT_SCOPE           = 'default_scope';
    public const ORGANIZATION_SCOPE      = 'organization_scope';
    public const USER_SCOPE              = 'user_scope';
    public const USER1_SCOPE             = 'user1_scope';
    public const USER_ORGANIZATION_SCOPE = 'user_organization_scope';

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
            LoadUser::class,
            LoadUserData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $defaultScope = $manager->getRepository(Scope::class)
            ->createQueryBuilder('s')
            ->orderBy('s.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
        $this->setReference(self::DEFAULT_SCOPE, $defaultScope);

        $userOrganizationScope = new Scope();
        $userOrganizationScope->setUser($this->getReference(self::USER));
        $userOrganizationScope->setOrganization($this->getReference(self::ORGANIZATION));
        $manager->persist($userOrganizationScope);
        $this->setReference(self::USER_ORGANIZATION_SCOPE, $userOrganizationScope);

        $organizationScope = new Scope();
        $organizationScope->setOrganization($this->getReference(self::ORGANIZATION));
        $manager->persist($organizationScope);
        $this->setReference(self::ORGANIZATION_SCOPE, $organizationScope);

        $userScope = new Scope();
        $userScope->setUser($this->getReference(self::USER));
        $manager->persist($userScope);
        $this->setReference(self::USER_SCOPE, $userScope);

        $user1Scope = new Scope();
        $user1Scope->setUser($this->getReference(self::USER1));
        $manager->persist($user1Scope);
        $this->setReference(self::USER1_SCOPE, $user1Scope);

        $manager->flush();
    }
}

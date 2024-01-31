<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership as TestEntity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadTestEntitiesData extends AbstractFixture implements DependentFixtureInterface
{
    public const FIRST_SIMPLE_USER_ENTITY = 'firstSimpleUserEntity';
    public const SECOND_SIMPLE_USER_ENTITY = 'secondSimpleUserEntity';
    public const THIRD_SIMPLE_USER_ENTITY = 'thirdSimpleUserEntity';
    public const FIRST_NOT_SIMPLE_USER_ENTITY = 'firstNotSimpleUserEntity';

    private static $testEntities = [
        [
            'name' => self::FIRST_SIMPLE_USER_ENTITY,
            'user' => LoadUserData::SIMPLE_USER
        ],
        [
            'name' => self::SECOND_SIMPLE_USER_ENTITY,
            'user' => LoadUserData::SIMPLE_USER
        ],
        [
            'name' => self::THIRD_SIMPLE_USER_ENTITY,
            'user' => LoadUserData::SIMPLE_USER
        ],
        [
            'name' => self::FIRST_NOT_SIMPLE_USER_ENTITY,
            'user' => LoadUserData::SIMPLE_USER_2
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadUserData::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach (self::$testEntities as $info) {
            $testEntity = new TestEntity();
            $testEntity->setName($info['name']);
            $testEntity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $testEntity->setOwner($this->getReference($info['user']));
            $manager->persist($testEntity);
            $this->setReference($info['name'], $testEntity);
        }
        $manager->flush();
    }
}

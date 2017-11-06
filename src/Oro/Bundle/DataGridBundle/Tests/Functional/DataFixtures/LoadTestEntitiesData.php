<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership as TestEntity;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTestEntitiesData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const FIRST_SIMPLE_USER_ENTITY = 'firstSimpleUserEntity';
    const SECOND_SIMPLE_USER_ENTITY = 'secondSimpleUserEntity';
    const THIRD_SIMPLE_USER_ENTITY = 'thirdSimpleUserEntity';
    const FIRST_NOT_SIMPLE_USER_ENTITY = 'firstNotSimpleUserEntity';

    use ContainerAwareTrait;

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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach (self::$testEntities as $info) {
            $testEntity = new TestEntity();
            $testEntity->setName($info['name']);
            $testEntity->setOrganization($organization);
            $testEntity->setOwner($this->getReference($info['user']));
            $manager->persist($testEntity);
            $this->setReference($info['name'], $testEntity);
        }

        $manager->flush();
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ExtendedRelationsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadExtendedRelationsData']);
    }

    public function testOwningSideEntity()
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repo */
        $repo = $doctrine->getManagerForClass('Extend\Entity\TestEntity1')
            ->getRepository('Extend\Entity\TestEntity1');

        $owningEntities = $repo->findByName('owning1');
        $this->assertCount(1, $owningEntities, 'owning1 was not found');
        $owningEntity = reset($owningEntities);

        // unidirectional many-to-one
        $this->assertEquals(
            'target1',
            $owningEntity->getUniM2OTarget()->getName(),
            'invalid target for unidirectional many-to-one'
        );
        // bidirectional many-to-one
        $this->assertEquals(
            'target1',
            $owningEntity->getBiM2OTarget()->getName(),
            'invalid target for bidirectional many-to-one'
        );

        // unidirectional many-to-many
        $this->assertCount(
            2,
            $owningEntity->getUniM2MTargets(),
            'invalid targets for unidirectional many-to-many'
        );
        $this->assertEquals(
            'target2',
            $owningEntity->getDefaultUniM2MTargets()->getName(),
            'invalid default target for unidirectional many-to-many'
        );
        // unidirectional many-to-many without default
        $this->assertCount(
            2,
            $owningEntity->getUniM2MNDTargets(),
            'invalid targets for unidirectional many-to-many without default'
        );
        // bidirectional many-to-many
        $this->assertCount(
            2,
            $owningEntity->getBiM2MTargets(),
            'invalid targets for bidirectional many-to-many'
        );
        $this->assertEquals(
            'target2',
            $owningEntity->getDefaultBiM2MTargets()->getName(),
            'invalid default target for bidirectional many-to-many'
        );
        // bidirectional many-to-many without default
        $this->assertCount(
            2,
            $owningEntity->getBiM2MNDTargets(),
            'invalid targets for bidirectional many-to-many without default'
        );

        // unidirectional one-to-many
        $this->assertCount(
            2,
            $owningEntity->getUniO2MTargets(),
            'invalid targets for unidirectional one-to-many'
        );
        $this->assertEquals(
            'target2',
            $owningEntity->getDefaultUniO2MTargets()->getName(),
            'invalid default target for unidirectional one-to-many'
        );
        // unidirectional one-to-many without default
        $this->assertCount(
            2,
            $owningEntity->getUniO2MNDTargets(),
            'invalid targets for unidirectional one-to-many without default'
        );
        // bidirectional one-to-many
        $this->assertCount(
            2,
            $owningEntity->getBiO2MTargets(),
            'invalid targets for bidirectional one-to-many'
        );
        $this->assertEquals(
            'target2',
            $owningEntity->getDefaultBiO2MTargets()->getName(),
            'invalid default target for bidirectional one-to-many'
        );
        // bidirectional one-to-many without default
        $this->assertCount(
            2,
            $owningEntity->getBiO2MNDTargets(),
            'invalid targets for bidirectional one-to-many without default'
        );
    }

    public function testTargetSideEntity()
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repo */
        $repo = $doctrine->getManagerForClass('Extend\Entity\TestEntity2')
            ->getRepository('Extend\Entity\TestEntity2');

        $targetEntities = $repo->findByName('target1');
        $this->assertCount(1, $targetEntities, 'target1 was not found');
        $targetEntity = reset($targetEntities);

        // bidirectional many-to-one
        $this->assertCount(
            1,
            $targetEntity->getBiM2OOwners(),
            'invalid owners for bidirectional many-to-one'
        );

        // bidirectional many-to-many
        $this->assertCount(
            1,
            $targetEntity->getBiM2MOwners(),
            'invalid owners for bidirectional many-to-many'
        );

        // bidirectional one-to-many
        $this->assertEquals(
            'owning1',
            $targetEntity->getBiO2MOwner()->getName(),
            'invalid owner for bidirectional one-to-many'
        );
    }
}

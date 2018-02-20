<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ExtendedRelationsCascadeTest extends WebTestCase
{
    const ENTITY1 = 'Extend\Entity\TestEntity3';
    const ENTITY2 = 'Extend\Entity\TestEntity4';

    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityManager $em */
        $em = $doctrine->getManagerForClass(self::ENTITY1);

        return $em;
    }

    /**
     * @param string $entityClass
     * @param string $name
     *
     * @return object
     */
    private function createEntity($entityClass, $name)
    {
        $entity = new $entityClass();
        $entity->setName($name);

        return $entity;
    }

    public function testCascadePersistForOwningSideOfManyToOne()
    {
        $owningEntity = $this->createEntity(self::ENTITY1, 'owning');
        $targetEntity = $this->createEntity(self::ENTITY2, 'target');

        $owningEntity->setBiM2OTarget($targetEntity);

        $em = $this->getEntityManager();
        $em->persist($owningEntity);
        $em->flush();

        $em->clear();
        $savedOwningEntity = $em->find(ClassUtils::getClass($owningEntity), $owningEntity->getId());
        $savedTargetEntity = $savedOwningEntity->getBiM2OTarget();
        self::assertNotNull($savedTargetEntity);
        self::assertSame($targetEntity->getId(), $savedTargetEntity->getId());
    }

    public function testCascadePersistForTargetSideOfManyToOne()
    {
        $owningEntity = $this->createEntity(self::ENTITY1, 'owning');
        $targetEntity = $this->createEntity(self::ENTITY2, 'target');

        $targetEntity->addBiM2OOwner($owningEntity);

        $em = $this->getEntityManager();
        $em->persist($targetEntity);
        $em->flush();

        $em->clear();
        $savedTargetEntity = $em->find(ClassUtils::getClass($targetEntity), $targetEntity->getId());
        $savedOwningEntities = $savedTargetEntity->getBiM2OOwners();
        self::assertCount(1, $savedOwningEntities);
        $savedOwningEntity = $savedOwningEntities->first();
        self::assertSame($owningEntity->getId(), $savedOwningEntity->getId());
    }

    public function testCascadePersistForOwningSideOfManyToMany()
    {
        $owningEntity = $this->createEntity(self::ENTITY1, 'owning');
        $targetEntity = $this->createEntity(self::ENTITY2, 'target');

        $owningEntity->addBiM2MTarget($targetEntity);

        $em = $this->getEntityManager();
        $em->persist($owningEntity);
        $em->flush();

        $em->clear();
        $savedOwningEntity = $em->find(ClassUtils::getClass($owningEntity), $owningEntity->getId());
        $savedTargetEntities = $savedOwningEntity->getBiM2MTargets();
        self::assertCount(1, $savedTargetEntities);
        $savedTargetEntity = $savedTargetEntities->first();
        self::assertSame($targetEntity->getId(), $savedTargetEntity->getId());
    }

    public function testCascadePersistForTargetSideOfManyToMany()
    {
        $owningEntity = $this->createEntity(self::ENTITY1, 'owning');
        $targetEntity = $this->createEntity(self::ENTITY2, 'target');

        $targetEntity->addBiM2MOwner($owningEntity);

        $em = $this->getEntityManager();
        $em->persist($targetEntity);
        $em->flush();

        $em->clear();
        $savedTargetEntity = $em->find(ClassUtils::getClass($targetEntity), $targetEntity->getId());
        $savedOwningEntities = $savedTargetEntity->getBiM2MOwners();
        self::assertCount(1, $savedOwningEntities);
        $savedOwningEntity = $savedOwningEntities->first();
        self::assertSame($owningEntity->getId(), $savedOwningEntity->getId());
    }

    public function testCascadePersistForOwningSideOfOneToMany()
    {
        $owningEntity = $this->createEntity(self::ENTITY2, 'owning');
        $targetEntity = $this->createEntity(self::ENTITY1, 'target');

        $owningEntity->setBiO2MOwner($targetEntity);

        $em = $this->getEntityManager();
        $em->persist($owningEntity);
        $em->flush();

        $em->clear();
        $savedOwningEntity = $em->find(ClassUtils::getClass($owningEntity), $owningEntity->getId());
        $savedTargetEntity = $savedOwningEntity->getBiO2MOwner();
        self::assertNotNull($savedTargetEntity);
        self::assertSame($targetEntity->getId(), $savedTargetEntity->getId());
    }

    public function testCascadePersistForTargetSideOfOneToMany()
    {
        $owningEntity = $this->createEntity(self::ENTITY2, 'owning');
        $targetEntity = $this->createEntity(self::ENTITY1, 'target');

        $targetEntity->addBiO2MTarget($owningEntity);

        $em = $this->getEntityManager();
        $em->persist($targetEntity);
        $em->flush();

        $em->clear();
        $savedTargetEntity = $em->find(ClassUtils::getClass($targetEntity), $targetEntity->getId());
        $savedOwningEntities = $savedTargetEntity->getBiO2MTargets();
        self::assertCount(1, $savedOwningEntities);
        $savedOwningEntity = $savedOwningEntities->first();
        self::assertSame($owningEntity->getId(), $savedOwningEntity->getId());
    }
}

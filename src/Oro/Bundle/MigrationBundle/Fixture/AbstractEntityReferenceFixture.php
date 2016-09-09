<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class AbstractEntityReferenceFixture extends AbstractFixture implements FixtureInterface
{
    /**
     * Returns array of object references.
     *
     * @param ObjectManager $objectManager
     * @param string $className
     * @return array
     * @see getObjectReferencesByIds
     */
    protected function getObjectReferences(ObjectManager $objectManager, $className)
    {
        $identifier = $objectManager->getClassMetadata($className)->getIdentifier();
        $idField    = reset($identifier);

        /** @var EntityRepository $objectRepository */
        $objectRepository = $objectManager->getRepository($className);

        $idsResult = $objectRepository
            ->createQueryBuilder('t')
            ->select('t.' . $idField)
            ->getQuery()
            ->getArrayResult();

        $ids = [];
        foreach ($idsResult as $result) {
            $ids[] = $result[$idField];
        }

        return $this->getObjectReferencesByIds($objectManager, $className, $ids);
    }

    /**
     * Returns array of object references by their ids. It's useful when ids are known and objects are used as
     * other entities' relation.
     *
     * @param ObjectManager $objectManager
     * @param string $className
     * @param array $ids
     * @return array
     */
    protected function getObjectReferencesByIds(ObjectManager $objectManager, $className, array $ids)
    {
        $entities = [];

        foreach ($ids as $id) {
            /** @var EntityManager $objectManager */
            $entities[] = $objectManager->getReference($className, $id);
        }

        return $entities;
    }
}

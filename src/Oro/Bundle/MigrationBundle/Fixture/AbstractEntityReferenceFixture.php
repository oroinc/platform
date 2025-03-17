<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;

/**
 * The base class for fixtures that need to get entity references.
 */
abstract class AbstractEntityReferenceFixture extends AbstractFixture
{
    /**
     * Returns array of entity references.
     *
     * @return object[]
     */
    protected function getObjectReferences(ObjectManager $objectManager, string $className): array
    {
        $identifier = $objectManager->getClassMetadata($className)->getIdentifier();
        $idField = reset($identifier);

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
     * Returns array of entity references by their ids. It's useful when ids are known and entities are used as
     * other entities' relation.
     *
     * @return object[]
     */
    protected function getObjectReferencesByIds(ObjectManager $objectManager, string $className, array $ids): array
    {
        $entities = [];
        foreach ($ids as $id) {
            $entities[] = $objectManager->getReference($className, $id);
        }

        return $entities;
    }
}

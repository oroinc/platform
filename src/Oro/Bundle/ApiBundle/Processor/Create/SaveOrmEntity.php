<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ApiBundle\Processor\Shared\SaveOrmEntity as BaseSaveOrmEntity;

/**
 * Saves new ORM entity to the database.
 */
class SaveOrmEntity extends BaseSaveOrmEntity
{
    /**
     * {@inheritdoc}
     */
    protected function saveEntity(EntityManager $em, $entity)
    {
        $em->persist($entity);
        $em->flush($entity);
    }
}

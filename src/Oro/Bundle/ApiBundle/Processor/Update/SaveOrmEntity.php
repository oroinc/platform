<?php

namespace Oro\Bundle\ApiBundle\Processor\Update;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ApiBundle\Processor\Shared\SaveOrmEntity as BaseSaveOrmEntity;

/**
 * Saves all changes of ORM entity to the database.
 */
class SaveOrmEntity extends BaseSaveOrmEntity
{
    /**
     * {@inheritdoc}
     */
    protected function saveEntity(EntityManager $em, $entity)
    {
        $em->flush($entity);
    }
}

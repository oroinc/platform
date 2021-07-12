<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Represents a handler to flush all ORM entities that are changed by API to the database.
 */
interface FlushDataHandlerInterface
{
    /**
     * Flushes all ORM entities that are changed by API to the database.
     *
     * @throws \Doctrine\DBAL\Exception when a database exception happens
     */
    public function flushData(EntityManagerInterface $entityManager, FlushDataHandlerContext $context): void;
}

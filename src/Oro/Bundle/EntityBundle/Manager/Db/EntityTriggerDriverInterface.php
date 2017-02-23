<?php

namespace Oro\Bundle\EntityBundle\Manager\Db;

interface EntityTriggerDriverInterface
{
    /**
     * This method disables all triggers for the particular entity table
     *
     * @return bool
     */
    public function disable();

    /**
     * This method enables back all triggers for the particular entity table
     *
     * @return bool
     */
    public function enable();

    /**
     * Ensuring that proper entityClass name is passed
     *
     * @param string $entityClass
     * @return $this
     */
    public function setEntityClass($entityClass);
}

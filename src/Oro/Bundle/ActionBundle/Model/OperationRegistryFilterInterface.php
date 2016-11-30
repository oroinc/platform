<?php

namespace Oro\Bundle\ActionBundle\Model;

interface OperationRegistryFilterInterface
{
    /**
     * @param array|Operation[] $operations
     * @param string $entityClass
     * @param string $route
     * @param string $datagrid
     * @return array|Operation[] of filtered operations
     */
    public function filter(array $operations, $entityClass, $route, $datagrid);
}

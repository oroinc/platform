<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Filter\Stub;

use Oro\Bundle\ActionBundle\Model\OperationRegistryFilterInterface;

class CallbackOperationRegistryFilter implements OperationRegistryFilterInterface
{
    /** @var callable */
    private $callable;

    /** @param callable $callable */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /** {@inheritdoc} */
    public function filter(array $operations, $entityClass, $route, $datagrid)
    {
        return call_user_func($this->callable, $operations, $entityClass, $datagrid, $route);
    }
}

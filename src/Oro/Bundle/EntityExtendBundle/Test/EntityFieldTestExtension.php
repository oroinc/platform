<?php

namespace Oro\Bundle\EntityExtendBundle\Test;

use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtensionInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;

/**
 * The base class for different entity field extensions usen in unit tests.
 */
class EntityFieldTestExtension implements EntityFieldExtensionInterface
{
    private array $expectations = [];

    /**
     * @param callable $expectation function (array $arguments, object $object, mixed &$result): bool
     */
    public function addExpectation(string $objectClass, string $name, callable $expectation): void
    {
        $this->expectations[$objectClass][$name][] = $expectation;
    }

    #[\Override]
    public function get(EntityFieldProcessTransport $transport): void
    {
        $this->processExpectations($transport);
    }

    #[\Override]
    public function set(EntityFieldProcessTransport $transport): void
    {
        $this->processExpectations($transport);
    }

    #[\Override]
    public function call(EntityFieldProcessTransport $transport): void
    {
        $this->processExpectations($transport);
    }

    #[\Override]
    public function isset(EntityFieldProcessTransport $transport): void
    {
        $this->processExpectations($transport);
    }

    #[\Override]
    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
    }

    #[\Override]
    public function methodExists(EntityFieldProcessTransport $transport): void
    {
    }

    #[\Override]
    public function getMethods(EntityFieldProcessTransport $transport): array
    {
        return [];
    }

    #[\Override]
    public function getMethodInfo(EntityFieldProcessTransport $transport): void
    {
    }

    private function processExpectations(EntityFieldProcessTransport $transport): void
    {
        $expectations = $this->expectations[\get_class($transport->getObject())][$transport->getName()] ?? [];
        foreach ($expectations as $expectation) {
            $result = null;
            if ($expectation($transport->getArguments(), $transport->getObject(), $result)) {
                $transport->setResult($result);
                $transport->setProcessed(true);
                break;
            }
        }
    }
}

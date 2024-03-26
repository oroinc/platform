<?php

declare(strict_types=1);

namespace Oro\Bundle\QueryDesignerBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * Fills `normalizedName`, required for advanced mode in Query Designer
 */
class EntityStructureOptionsListener
{
    private EntityAliasResolver $entityAliasResolver;

    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    public function onOptionsRequest(EntityStructureOptionsEvent $event): void
    {
        $data = $event->getData();

        $aliases = [];

        foreach ($data as $entityStructure) {
            $aliases[$entityStructure->getClassName()] = $entityStructure->getAlias();
        }

        foreach ($data as $entityStructure) {
            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                if (!$field->getNormalizedName()) {
                    $field->setNormalizedName($this->getNormalizedName($field->getName(), $aliases));
                }
            }
        }
        $event->setData($data);
    }

    protected function getNormalizedName(string $name, array $aliases): string
    {
        $data = explode('::', $name);
        if (1 === count($data)) {
            return $name;
        }
        [$class, $field] = $data;

        $entityAlias = $aliases[$class] ?? null;
        if (!$entityAlias) {
            if ($this->entityAliasResolver->hasAlias($class)) {
                $entityAlias = $this->entityAliasResolver->getAlias($class);
            } else {
                $parts = explode('\\', $class);
                $entityAlias = strtolower(end($parts));
            }
        }

        return sprintf('%s_%s', $entityAlias, $field);
    }
}

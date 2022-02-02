<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Doctrine\Inflector\Inflector;

/**
 * The entity names provider to help building auto-generated API documentation.
 */
class EntityNameProvider
{
    private EntityDescriptionProvider $entityDescriptionProvider;
    private Inflector $inflector;

    public function __construct(EntityDescriptionProvider $entityDescriptionProvider, Inflector $inflector)
    {
        $this->entityDescriptionProvider = $entityDescriptionProvider;
        $this->inflector = $inflector;
    }

    public function getEntityName(string $entityClass, bool $lowercase = false): string
    {
        $result = $this->entityDescriptionProvider->getEntityDescription($entityClass);
        if (!$result) {
            $result = $this->humanizeEntityClass($entityClass);
        }
        if ($lowercase) {
            $result = mb_strtolower($result);
        }

        return $result;
    }

    public function getEntityPluralName(string $entityClass, bool $lowercase = false): string
    {
        $result = $this->entityDescriptionProvider->getEntityPluralDescription($entityClass);
        if (!$result) {
            $result = $this->inflector->pluralize($this->humanizeEntityClass($entityClass));
        }
        if ($lowercase) {
            $result = mb_strtolower($result);
        }

        return $result;
    }

    private function humanizeEntityClass(string $entityClass): string
    {
        $shortEntityClass = $entityClass;
        $lastDelimiter = strrpos($shortEntityClass, '\\');
        if (false !== $lastDelimiter) {
            $shortEntityClass = substr($shortEntityClass, $lastDelimiter + 1);
        }

        // convert "SomeClassName" to "Some Class Name".
        return preg_replace('~(?<=\\w)([A-Z])~', ' $1', $shortEntityClass);
    }
}

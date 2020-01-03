<?php

namespace Oro\Bundle\ScopeBundle\Model;

/**
 * The trait that provides an algorithm to convert ScopeCriteria parameter value to a string.
 */
trait NormalizeParameterValueTrait
{
    /**
     * @param mixed $value
     *
     * @return string|null
     */
    private function normalizeParameterValue($value): ?string
    {
        $result = null;
        if (\is_object($value)) {
            $id = $this->getEntityId($value);
            if (null !== $id) {
                $result = (string)$id;
            }
        } elseif (\is_array($value)) {
            $ids = [];
            foreach ($value as $val) {
                $id = null;
                if (\is_object($val)) {
                    $id = $this->getEntityId($val);
                } else {
                    $id = $val;
                }
                $ids[] = $id;
            }
            $result = implode(',', $ids);
        } elseif (null !== $value) {
            $result = (string)$value;
        }

        return $result;
    }

    /**
     * @param object $entity
     *
     * @return mixed
     */
    private function getEntityId($entity)
    {
        return $entity->getId();
    }
}

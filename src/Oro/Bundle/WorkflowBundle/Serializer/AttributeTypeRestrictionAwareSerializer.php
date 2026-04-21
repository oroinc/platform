<?php

namespace Oro\Bundle\WorkflowBundle\Serializer;

use Symfony\Component\Serializer\SerializerInterface;

/**
 * Defines the contract for serializers that are aware of workflow attribute type restrictions.
 */
interface AttributeTypeRestrictionAwareSerializer extends SerializerInterface
{
    public function setRestrictedTypes(array $restrictedTypes): void;

    public function isRestrictedType(string $type): bool;
}

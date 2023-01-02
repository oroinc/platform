<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Transforms an entity class name to API entity type.
 */
class EntityClassToEntityTypeTransformer implements DataTransformerInterface
{
    private ValueNormalizer $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function transform(mixed $value, array $config, array $context): mixed
    {
        if (empty($value)) {
            return $value;
        }

        return $this->valueNormalizer->normalizeValue(
            $value,
            DataType::ENTITY_TYPE,
            $context[Context::REQUEST_TYPE]
        );
    }
}

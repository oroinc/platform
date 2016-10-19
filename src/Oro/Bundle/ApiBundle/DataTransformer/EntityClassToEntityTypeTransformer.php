<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

class EntityClassToEntityTypeTransformer implements DataTransformerInterface
{
    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($class, $property, $value, array $config, array $context)
    {
        if (empty($value)) {
            return $value;
        }

        try {
            return $this->valueNormalizer->normalizeValue(
                $value,
                DataType::ENTITY_TYPE,
                $context[Context::REQUEST_TYPE]
            );
        } catch (EntityAliasNotFoundException $e) {
            throw new RuntimeException(
                sprintf(
                    'The "%s" class cannot be converted to the entity type.'
                    . 'Be sure that this entity is configured to be available through API.',
                    $value
                )
            );
        }
    }
}

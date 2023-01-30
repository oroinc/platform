<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The transformer that keeps the entity identifier value as is.
 */
class NullEntityIdTransformer implements EntityIdTransformerInterface
{
    private static ?NullEntityIdTransformer $instance = null;

    /**
     * A private constructor to prevent create an instance of this class explicitly.
     */
    private function __construct()
    {
    }

    public static function getInstance(): NullEntityIdTransformer
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(mixed $id, EntityMetadata $metadata): mixed
    {
        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value, EntityMetadata $metadata): mixed
    {
        return $value;
    }
}

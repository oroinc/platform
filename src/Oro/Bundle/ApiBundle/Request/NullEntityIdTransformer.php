<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The transformer that keeps the entity identifier value as is.
 */
class NullEntityIdTransformer implements EntityIdTransformerInterface
{
    /** @var NullEntityIdTransformer|null */
    private static $instance;

    /**
     * A private constructor to prevent create an instance of this class explicitly.
     */
    private function __construct()
    {
    }

    /**
     * @return NullEntityIdTransformer
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($id, EntityMetadata $metadata)
    {
        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value, EntityMetadata $metadata)
    {
        return $value;
    }
}

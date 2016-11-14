<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

final class NullTransformer implements DataTransformerInterface
{
    /** @var NullTransformer|null */
    private static $instance;

    /**
     * A private constructor to prevent create an instance of this class explicitly
     */
    private function __construct()
    {
    }

    /**
     * @return NullTransformer
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
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}

<?php

namespace Oro\Component\DoctrineUtils\Inflector;

use Doctrine\Inflector\Inflector as DoctrineInflector;
use Doctrine\Inflector\Rules\English\InflectorFactory as BaseInflectorFactory;

/**
 * The base class for instantiating the doctrine inflector.
 */
final class InflectorFactory
{
    /**
     * @var DoctrineInflector
     */
    private static $instance;

    public static function create(): DoctrineInflector
    {
        if (!InflectorFactory::$instance) {
            InflectorFactory::$instance = (new BaseInflectorFactory())->build();
        }

        return InflectorFactory::$instance;
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}

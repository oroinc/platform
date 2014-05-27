<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use CG\Generator\PhpClass;

interface ExtendEntityGeneratorExtension
{
    /**
     * Check if generator extension can be applied based on configuration
     *
     * @param array $config
     *
     * @return bool
     */
    public function supports(array $config);

    /**
     * Apply extension to entity configuration before it will be generated as PHP, YAML files
     *
     * @param array $config
     *
     * @return void
     */
    public function preProcessEntityConfiguration(array &$config);

    /**
     * @param array    $config
     * @param PhpClass $class
     *
     * @return void
     */
    public function generate(array &$config, PhpClass $class);
}

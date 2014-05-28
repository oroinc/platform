<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use CG\Generator\PhpClass;

interface ExtendEntityGeneratorExtension
{
    const ACTION_PRE_PROCESS = 'pre-process';
    const ACTION_GENERATE    = 'generate';

    /**
     * Check if generator extension supports configuration pre-processing or can generate code
     *
     * @param string $actionType pre-process or generate
     * @param array  $config     whole configuration when actionType is pre-process,
     *                           entity configuration when actionType is generate
     *
     * @return bool
     */
    public function supports($actionType, array $config);

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

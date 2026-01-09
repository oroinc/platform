<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

/**
 * Defines the contract for processing configuration imports with support for nested imports.
 *
 * Implementations handle loading and processing configuration content from various sources,
 * with the ability to delegate nested import processing to parent processors to ensure
 * correct merge order and dependency resolution.
 */
interface ConfigImportProcessorInterface
{
    public function process(array $content, \SplFileInfo $contentSource): array;

    /**
     * The processor is aware of parent one to be able to initiate processing other imports
     * that have been loaded by it or MUST be processed before/after the current one.
     *
     * For example:
     * Current processor loads as in its import payload new file that contains other import directives.
     * The current processor does not know how to process them, but it is necessary
     * for it to be able to merge config correctly.
     * So now it can call $parent->process(..) to do so.
     */
    public function setParent(ConfigImportProcessorInterface $parentProcessor);
}

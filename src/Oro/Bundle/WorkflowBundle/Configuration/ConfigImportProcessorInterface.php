<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

interface ConfigImportProcessorInterface
{
    /**
     * @param array $content
     * @param \SplFileInfo $contentSource
     * @return array
     */
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
     *
     * @param ConfigImportProcessorInterface $parentProcessor
     */
    public function setParent(ConfigImportProcessorInterface $parentProcessor);
}

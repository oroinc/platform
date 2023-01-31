<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Generates the name of a file that will be used to store request data.
 */
class GenerateTargetFileName implements ProcessorInterface
{
    private FileNameProvider $fileNameProvider;

    public function __construct(FileNameProvider $fileNameProvider)
    {
        $this->fileNameProvider = $fileNameProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if ($context->getTargetFileName()) {
            // context already have file name
            return;
        }

        $context->setTargetFileName($this->fileNameProvider->getDataFileName());
    }
}

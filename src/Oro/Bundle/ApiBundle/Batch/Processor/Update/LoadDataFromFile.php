<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * A base processor for loading data from a chunk file.
 */
abstract class LoadDataFromFile implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $data = $context->getResult();
        if (null !== $data) {
            // data were already loaded
            return;
        }

        $context->setResult(
            $this->loadData($context->getFile()->getFileName(), $context->getFileManager())
        );
    }

    abstract protected function loadData(string $fileName, FileManager $fileManager): array;
}

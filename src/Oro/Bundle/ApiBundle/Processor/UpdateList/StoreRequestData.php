<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\GaufretteBundle\Stream\ReadonlyResourceStream;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Stores request data to Gaufrette filesystem.
 * In case input data is empty - adds validation error.
 */
class StoreRequestData implements ProcessorInterface
{
    private FileManager $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        $fileName = $context->getTargetFileName();
        if (!$fileName) {
            throw new \RuntimeException('The target file name was not set to the context.');
        }

        $requestData = $context->getRequestData();
        if (null === $requestData) {
            // request data already stored
            return;
        }

        if (!$this->writeRequestDataToStorage($requestData, $fileName)) {
            $context->setTargetFileName(null);
            $context->addError(
                Error::createValidationError(Constraint::REQUEST_DATA, 'The request data should not be empty.')
            );
        }

        $context->setRequestData(null);
    }

    private function writeRequestDataToStorage(mixed $requestData, string $fileName): bool
    {
        if (\is_resource($requestData)) {
            return $this->fileManager->writeStreamToStorage(new ReadonlyResourceStream($requestData), $fileName, true);
        }

        if (!\is_array($requestData)) {
            throw new \RuntimeException(\sprintf(
                'The request data should be resource or array, got "%s".',
                get_debug_type($requestData)
            ));
        }

        if (!$requestData) {
            return false;
        }

        $this->fileManager->writeToStorage(json_encode($requestData, JSON_THROW_ON_ERROR), $fileName);

        return true;
    }
}

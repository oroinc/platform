<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "folders" field for EmailUser entity.
 */
class ComputeEmailUserFolders implements ProcessorInterface
{
    private const FOLDERS_FIELD_NAME = 'folders';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if ($context->isFieldRequested(self::FOLDERS_FIELD_NAME)) {
            $foldersFieldName = $context->getResultFieldName('folders');
            $foldersConfig = $context->getConfig()->getField($foldersFieldName)->getTargetEntity();
            $typeFieldName = $context->getResultFieldName('type', $foldersConfig);
            $nameFieldName = $context->getResultFieldName('name', $foldersConfig);
            $fullNameFieldName = $context->getResultFieldName('fullName', $foldersConfig);
            $folders = [];
            foreach ($data[$foldersFieldName] as $item) {
                $folders[] = [
                    'type' => $item[$typeFieldName],
                    'name' => $item[$nameFieldName],
                    'path' => $item[$fullNameFieldName]
                ];
            }
            $data[self::FOLDERS_FIELD_NAME] = $folders;
            $context->setData($data);
        }
    }
}

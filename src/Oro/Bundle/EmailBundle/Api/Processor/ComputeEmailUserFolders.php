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
            $folders = [];
            foreach ($data['_folders'] as $item) {
                $folders[] = ['type' => $item['type'], 'name' => $item['name'], 'path' => $item['fullName']];
            }
            $data[self::FOLDERS_FIELD_NAME] = $folders;
            $context->setData($data);
        }
    }
}

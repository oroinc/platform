<?php

namespace Oro\Bundle\AttachmentBundle\Api\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Symfony\Component\Form\FormInterface;

/**
 * Represents a processor that handle additional options for files in {@see MultiFileEntityType}.
 */
interface MultiFileEntityOptionProcessorInterface
{
    public function process(FileItem $fileItem, array $dataItem, string $dataItemKey, FormInterface $form): void;
}

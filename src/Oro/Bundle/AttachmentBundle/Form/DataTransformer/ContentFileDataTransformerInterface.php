<?php

namespace Oro\Bundle\AttachmentBundle\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Represents transformer for a value between raw file content and File entity
 */
interface ContentFileDataTransformerInterface extends DataTransformerInterface
{
    public function setFileName(string $fileName): void;

    public function transform(mixed $value): ?File;

    public function reverseTransform(mixed $value): ?string;
}

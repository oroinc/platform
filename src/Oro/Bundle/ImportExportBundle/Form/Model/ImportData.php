<?php

namespace Oro\Bundle\ImportExportBundle\Form\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a model for {@see \Oro\Bundle\ImportExportBundle\Form\Type\ImportType}.
 */
class ImportData
{
    #[Assert\NotBlank]
    private ?UploadedFile $file = null;

    #[Assert\NotBlank]
    private ?string $processorAlias = null;

    public function setFile(?UploadedFile $file): void
    {
        $this->file = $file;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setProcessorAlias(?string $processorAlias): void
    {
        $this->processorAlias = $processorAlias;
    }

    public function getProcessorAlias(): ?string
    {
        return $this->processorAlias;
    }
}

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

    /**
     * @param UploadedFile $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $processorAlias
     */
    public function setProcessorAlias($processorAlias)
    {
        $this->processorAlias = $processorAlias;
    }

    /**
     * @return string
     */
    public function getProcessorAlias()
    {
        return $this->processorAlias;
    }
}

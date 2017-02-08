<?php

namespace Oro\Bundle\ImportExportBundle\Form\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class ImportData
{
    /**
     * @var UploadedFile
     *
     * @Assert\File(mimeTypes = {"text/plain", "text/csv"})
     * @Assert\NotBlank()
     */
    protected $file;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    protected $processorAlias;

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

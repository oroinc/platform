<?php

namespace Oro\Bundle\ImportExportBundle\Form\Model;

/**
 * Data model for export form submission.
 *
 * This class holds the data submitted through the export form, specifically
 * the processor alias selected by the user. It serves as the data class for
 * the {@see ExportType} form, allowing the form framework to bind submitted data
 * to this object.
 */
class ExportData
{
    /**
     * @var string
     */
    protected $processorAlias;

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

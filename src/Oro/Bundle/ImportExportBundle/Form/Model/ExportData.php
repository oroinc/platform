<?php

namespace Oro\Bundle\ImportExportBundle\Form\Model;

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

<?php

namespace Oro\Bundle\TrackingBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class DataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'idgoal'  => 'name',
            'revenue' => 'value',
            'idsite'  => 'website',
            '_uid'    => 'user',
            '_rcn'    => 'code',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}

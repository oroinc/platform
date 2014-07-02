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
            'e_n'     => 'name',
            'e_v'     => 'value',
            'idsite'  => 'website',
            '_uid'    => 'user',
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

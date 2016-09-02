<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;

class LocalizedValueProperty extends AbstractProperty
{
    const NAME = 'localized_value';

    /**
     * {@inheritdoc}
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        return $record->getValue($this->get(self::NAME_KEY));
    }
}

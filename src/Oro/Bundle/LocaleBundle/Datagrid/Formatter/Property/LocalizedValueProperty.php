<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;

class LocalizedValueProperty extends AbstractProperty
{
    const NAME = 'localized_value';
    const ALLOW_EMPTY = 'allow_empty';

    /**
     * {@inheritdoc}
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        return $record->getValue($this->get(self::NAME_KEY));
    }
}

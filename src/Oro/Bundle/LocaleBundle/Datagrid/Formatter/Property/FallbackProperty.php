<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;

class FallbackProperty extends AbstractProperty
{
    const NAME = 'fallback';

    /**
     * {@inheritdoc}
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        return $record->getValue($this->get(self::NAME_KEY));
    }
}

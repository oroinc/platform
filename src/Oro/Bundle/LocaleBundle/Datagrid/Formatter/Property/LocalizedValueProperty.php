<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;

/**
 * Formats localized values for display in datagrids.
 *
 * This property formatter retrieves localized fallback values from records and prepares them
 * for rendering in datagrid columns. It supports optional empty value handling
 * through the {@see LocalizedValueProperty::ALLOW_EMPTY} configuration option.
 */
class LocalizedValueProperty extends AbstractProperty
{
    public const NAME = 'localized_value';
    public const ALLOW_EMPTY = 'allow_empty';

    #[\Override]
    protected function getRawValue(ResultRecordInterface $record)
    {
        return $record->getValue($this->get(self::NAME_KEY));
    }
}

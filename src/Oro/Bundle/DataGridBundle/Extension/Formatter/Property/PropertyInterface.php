<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Interface PropertyInterface
 * @package Oro\Bundle\DataGridBundle\Extension\Formatter\Property
 *
 * Every property should be stateless
 */
interface PropertyInterface
{
    const TYPE_DATE         = 'date';
    const TYPE_DATETIME     = 'datetime';
    const TYPE_TIME         = 'time';
    const TYPE_DECIMAL      = 'decimal';
    const TYPE_INTEGER      = 'integer';
    const TYPE_PERCENT      = 'percent';
    const TYPE_CURRENCY     = 'currency';
    const TYPE_SELECT       = 'select';
    const TYPE_MULTI_SELECT = 'multi-select';
    const TYPE_STRING       = 'string';
    const TYPE_HTML         = 'html';
    const TYPE_BOOLEAN      = 'boolean';
    const TYPE_ARRAY        = 'array';
    const TYPE_SIMPLE_ARRAY = 'simple_array';
    const TYPE_ROW_ARRAY    = 'row_array';

    const METADATA_NAME_KEY = 'name';
    const METADATA_TYPE_KEY = 'type';

    const DISABLED_KEY      = 'disabled';
    const TYPE_KEY          = 'type';
    const NAME_KEY          = 'name';
    const DATA_NAME_KEY     = 'data_name';
    const TRANSLATABLE_KEY  = 'translatable';
    const FRONTEND_TYPE_KEY = 'frontend_type';

    /**
     * Initialize property for each cell
     *
     * @param PropertyConfiguration $params
     *
     * @return $this
     */
    public function init(PropertyConfiguration $params);

    /**
     * Get field value from data
     *
     * @param ResultRecordInterface $record
     *
     * @return mixed
     */
    public function getValue(ResultRecordInterface $record);

    /**
     * Returns field metadata
     *
     * @return array
     */
    public function getMetadata();
}

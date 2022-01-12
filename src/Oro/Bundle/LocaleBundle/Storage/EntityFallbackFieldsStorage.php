<?php

namespace Oro\Bundle\LocaleBundle\Storage;

/**
 * Store all classes that have fields use LocalizedFallbackValue or AbstractLocalizedFallbackValue
 */
class EntityFallbackFieldsStorage
{
    /** @var array [class name => [singular field name => field name, ...], ...] */
    private array $fieldMap;

    /**
     * @param array $fieldMap [class name => [singular field name => field name, ...], ...]
     */
    public function __construct(array $fieldMap)
    {
        $this->fieldMap = $fieldMap;
    }

    /**
     * @return array
     */
    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }
}

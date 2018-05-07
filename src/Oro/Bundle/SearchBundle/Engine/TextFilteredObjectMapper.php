<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * TODO: Should be removed after https://magecore.atlassian.net/browse/BB-12955
 */
class TextFilteredObjectMapper extends ObjectMapper
{
    /**
     * {@inheritdoc}
     */
    protected function setDataValue($alias, $objectData, $fieldConfig, $value, $isArray = false)
    {
        $objectData = parent::setDataValue($alias, $objectData, $fieldConfig, $value, $isArray);

        if (isset($objectData[Query::TYPE_TEXT])) {
            foreach ($objectData[Query::TYPE_TEXT] as $fieldName => &$fieldValue) {
                $fieldValue = $this->clearTextValue($fieldName, $fieldValue);
            }
        }

        return $objectData;
    }

    /**
     * Clear HTML in text fields
     *
     * {@inheritdoc}
     */
    protected function clearTextValue($fieldName, $value)
    {
        $value = $this->htmlTagHelper->stripTags((string)$value);
        $value = $this->htmlTagHelper->stripLongWords($value);

        return $value;
    }
}

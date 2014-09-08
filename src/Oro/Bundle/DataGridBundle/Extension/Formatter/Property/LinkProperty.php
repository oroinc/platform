<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

class LinkProperty extends UrlProperty
{
    /**
     * {@inheritdoc}
     */
    public function getRawValue(ResultRecordInterface $record)
    {
        try {
            $linkTitle = $record->getValue($this->getOr(self::DATA_NAME_KEY, $this->get(self::NAME_KEY)));
        } catch (\LogicException $e) {
            // default value: empty string, no link
            return '';
        }
        
        return '<a href="' . parent::getRawValue($record) . '">' . $linkTitle . '</a>';
    }
}

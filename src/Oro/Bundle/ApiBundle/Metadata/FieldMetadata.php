<?php

namespace Oro\Bundle\ApiBundle\Metadata;

class FieldMetadata extends PropertyMetadata
{
    /** the maximum length of the field data */
    const MAX_LENGTH = 'maxLength';

    /**
     * Gets the maximum length of the field data.
     *
     * @return int
     */
    public function getMaxLength()
    {
        return $this->get(self::MAX_LENGTH);
    }

    /**
     * Sets the maximum length of the field data.
     *
     * @param int $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->set(self::MAX_LENGTH, $maxLength);
    }
}

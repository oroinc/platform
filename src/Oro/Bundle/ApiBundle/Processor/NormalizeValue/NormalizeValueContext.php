<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class NormalizeValueContext extends ApiContext
{
    /** a data-type of a value */
    const DATA_TYPE = 'dataType';

    /** a regular expression that can be used to validate a value */
    const REQUIREMENT = 'requirement';

    /**
     * Gets data-type of a value.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->get(self::DATA_TYPE);
    }

    /**
     * Sets data-type of a value.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->set(self::DATA_TYPE, $dataType);
    }

    /**
     * Checks whether a regular expression that can be used to validate a value exists.
     *
     * @return bool
     */
    public function hasRequirement()
    {
        return $this->has(self::REQUIREMENT);
    }

    /**
     * Gets a regular expression that can be used to validate a value.
     *
     * @return string|null
     */
    public function getRequirement()
    {
        return $this->get(self::REQUIREMENT);
    }

    /**
     * Sets a regular expression that can be used to validate a value.
     *
     * @param string|null $requirement
     */
    public function setRequirement($requirement)
    {
        $this->set(self::REQUIREMENT, $requirement);
    }

    /**
     * Removes a regular expression that can be used to validate a value.
     */
    public function removeRequirement()
    {
        $this->remove(self::REQUIREMENT);
    }
}

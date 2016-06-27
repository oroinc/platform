<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class NormalizeValueContext extends ApiContext
{
    /** indicates whether a suitable processor has processed a value */
    const PROCESSED = 'processed';

    /** a data-type of a value */
    const DATA_TYPE = 'dataType';

    /** a regular expression that can be used to validate a value */
    const REQUIREMENT = 'requirement';

    /** determines if a value can be an array */
    const ARRAY_ALLOWED = 'arrayAllowed';

    /** a delimiter that should be used to split a string to separate elements */
    const ARRAY_DELIMITER = 'arrayDelimiter';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        $this->set(self::ARRAY_DELIMITER, ',');
    }

    /**
     * Gets a flag indicates whether a suitable processor has processed a value.
     *
     * @return bool
     */
    public function isProcessed()
    {
        return (bool)$this->get(self::PROCESSED);
    }

    /**
     * Sets a flag indicates whether a suitable processor has processed a value.
     *
     * @param bool $flag
     */
    public function setProcessed($flag)
    {
        $this->set(self::PROCESSED, $flag);
    }

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

    /**
     * Gets a flag determines if a value can be an array.
     *
     * @return bool|null
     */
    public function isArrayAllowed()
    {
        return $this->get(self::ARRAY_ALLOWED);
    }

    /**
     * Sets a flag determines if a value can be an array.
     *
     * @param bool|null $flag
     */
    public function setArrayAllowed($flag)
    {
        $this->set(self::ARRAY_ALLOWED, $flag);
    }

    /**
     * Gets a delimiter that should be used to split a string to separate elements.
     *
     * @return string|null
     */
    public function getArrayDelimiter()
    {
        return $this->get(self::ARRAY_DELIMITER);
    }

    /**
     * Sets a delimiter that should be used to split a string to separate elements.
     *
     * @param string|null $delimiter
     */
    public function setArrayDelimiter($delimiter)
    {
        $this->set(self::ARRAY_DELIMITER, $delimiter);
    }
}

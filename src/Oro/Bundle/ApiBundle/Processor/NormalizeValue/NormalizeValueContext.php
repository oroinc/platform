<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\Context;

/**
 * The execution context for processors for "normalize_value" action.
 */
class NormalizeValueContext extends Context
{
    /** the request type */
    const REQUEST_TYPE = 'requestType';

    /** API version */
    const VERSION = 'version';

    /** a data-type of a value */
    const DATA_TYPE = 'dataType';

    /** a regular expression that can be used to validate a value */
    const REQUIREMENT = 'requirement';

    /** determines if a value can be an array */
    const ARRAY_ALLOWED = 'arrayAllowed';

    /** determines if a value can be a pair of "from" and "to" values */
    const RANGE_ALLOWED = 'rangeAllowed';

    /** @var string */
    private $arrayDelimiter = ',';

    /** @var string */
    private $rangeDelimiter = '..';

    /** @var bool */
    private $processed = false;

    public function __construct()
    {
        $this->set(self::REQUEST_TYPE, new RequestType([]));
    }

    /**
     * Gets the current request type.
     * A request can belong to several types, e.g. "rest" and "json_api".
     *
     * @return RequestType
     */
    public function getRequestType()
    {
        return $this->get(self::REQUEST_TYPE);
    }

    /**
     * Gets API version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->get(self::VERSION);
    }

    /**
     * Sets API version
     *
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->set(self::VERSION, $version);
    }

    /**
     * Gets a flag indicates whether a suitable processor has processed a value.
     *
     * @return bool
     */
    public function isProcessed()
    {
        return $this->processed;
    }

    /**
     * Sets a flag indicates whether a suitable processor has processed a value.
     *
     * @param bool $flag
     */
    public function setProcessed($flag)
    {
        $this->processed = $flag;
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
     * Gets a flag determines if a value can be a pair of "from" and "to" values.
     *
     * @return bool|null
     */
    public function isRangeAllowed()
    {
        return $this->get(self::RANGE_ALLOWED);
    }

    /**
     * Sets a flag determines if a value can be a pair of "from" and "to" values.
     *
     * @param bool|null $flag
     */
    public function setRangeAllowed($flag)
    {
        $this->set(self::RANGE_ALLOWED, $flag);
    }

    /**
     * Gets a delimiter that should be used to split a string to separate elements.
     *
     * @return string
     */
    public function getArrayDelimiter()
    {
        return $this->arrayDelimiter;
    }

    /**
     * Sets a delimiter that should be used to split a string to separate elements.
     *
     * @param string $delimiter
     */
    public function setArrayDelimiter($delimiter)
    {
        $this->arrayDelimiter = $delimiter;
    }

    /**
     * Gets a delimiter that should be used to split a string to a pair of "from" and "to" values.
     *
     * @return string
     */
    public function getRangeDelimiter()
    {
        return $this->rangeDelimiter;
    }

    /**
     * Sets a delimiter that should be used to split a string to a pair of "from" and "to" values.
     *
     * @param string $delimiter
     */
    public function setRangeDelimiter($delimiter)
    {
        $this->rangeDelimiter = $delimiter;
    }
}

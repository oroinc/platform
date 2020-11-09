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
    private const REQUEST_TYPE = 'requestType';

    /** API version */
    private const VERSION = 'version';

    /** a data-type of a value */
    private const DATA_TYPE = 'dataType';

    /** @var bool */
    private $processed = false;

    /** @var string|null */
    private $requirement;

    /** @var bool */
    private $arrayAllowed = false;

    /** @var bool */
    private $rangeAllowed = false;

    /** @var string */
    private $arrayDelimiter = ',';

    /** @var string */
    private $rangeDelimiter = '..';

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
    public function getRequestType(): RequestType
    {
        return $this->get(self::REQUEST_TYPE);
    }

    /**
     * Gets API version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->get(self::VERSION);
    }

    /**
     * Sets API version.
     *
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->set(self::VERSION, $version);
    }

    /**
     * Gets a flag indicates whether a suitable processor has processed a value.
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * Sets a flag indicates whether a suitable processor has processed a value.
     *
     * @param bool $flag
     */
    public function setProcessed(bool $flag): void
    {
        $this->processed = $flag;
    }

    /**
     * Gets data-type of a value.
     *
     * @return string
     */
    public function getDataType(): string
    {
        return $this->get(self::DATA_TYPE);
    }

    /**
     * Sets data-type of a value.
     *
     * @param string $dataType
     */
    public function setDataType(string $dataType): void
    {
        $this->set(self::DATA_TYPE, $dataType);
    }

    /**
     * Checks whether a regular expression that can be used to validate a value exists.
     *
     * @return bool
     */
    public function hasRequirement(): bool
    {
        return null !== $this->requirement;
    }

    /**
     * Gets a regular expression that can be used to validate a value.
     *
     * @return string|null
     */
    public function getRequirement(): ?string
    {
        return $this->requirement;
    }

    /**
     * Sets a regular expression that can be used to validate a value.
     *
     * @param string $requirement
     */
    public function setRequirement(string $requirement): void
    {
        $this->requirement = $requirement;
    }

    /**
     * Removes a regular expression that can be used to validate a value.
     */
    public function removeRequirement(): void
    {
        $this->requirement = null;
    }

    /**
     * Gets a flag determines if a value can be an array.
     *
     * @return bool
     */
    public function isArrayAllowed(): bool
    {
        return $this->arrayAllowed;
    }

    /**
     * Sets a flag determines if a value can be an array.
     *
     * @param bool $flag
     */
    public function setArrayAllowed(bool $flag): void
    {
        $this->arrayAllowed = $flag;
    }

    /**
     * Gets a flag determines if a value can be a pair of "from" and "to" values.
     *
     * @return bool
     */
    public function isRangeAllowed(): bool
    {
        return $this->rangeAllowed;
    }

    /**
     * Sets a flag determines if a value can be a pair of "from" and "to" values.
     *
     * @param bool $flag
     */
    public function setRangeAllowed(bool $flag): void
    {
        $this->rangeAllowed = $flag;
    }

    /**
     * Gets a delimiter that should be used to split a string to separate elements.
     *
     * @return string
     */
    public function getArrayDelimiter(): string
    {
        return $this->arrayDelimiter;
    }

    /**
     * Sets a delimiter that should be used to split a string to separate elements.
     *
     * @param string $delimiter
     */
    public function setArrayDelimiter(string $delimiter): void
    {
        $this->arrayDelimiter = $delimiter;
    }

    /**
     * Gets a delimiter that should be used to split a string to a pair of "from" and "to" values.
     *
     * @return string
     */
    public function getRangeDelimiter(): string
    {
        return $this->rangeDelimiter;
    }

    /**
     * Sets a delimiter that should be used to split a string to a pair of "from" and "to" values.
     *
     * @param string $delimiter
     */
    public function setRangeDelimiter(string $delimiter): void
    {
        $this->rangeDelimiter = $delimiter;
    }
}

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

    private bool $processed = false;
    private ?string $requirement = null;
    private bool $arrayAllowed = false;
    private bool $rangeAllowed = false;
    private string $arrayDelimiter = ',';
    private string $rangeDelimiter = '..';

    public function __construct()
    {
        $this->set(self::REQUEST_TYPE, new RequestType([]));
    }

    /**
     * Gets the current request type.
     * A request can belong to several types, e.g. "rest" and "json_api".
     */
    public function getRequestType(): RequestType
    {
        return $this->get(self::REQUEST_TYPE);
    }

    /**
     * Gets API version.
     */
    public function getVersion(): string
    {
        return $this->get(self::VERSION);
    }

    /**
     * Sets API version.
     */
    public function setVersion(string $version): void
    {
        $this->set(self::VERSION, $version);
    }

    /**
     * Gets a flag indicates whether a suitable processor has processed a value.
     */
    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * Sets a flag indicates whether a suitable processor has processed a value.
     */
    public function setProcessed(bool $flag): void
    {
        $this->processed = $flag;
    }

    /**
     * Gets data-type of a value.
     */
    public function getDataType(): string
    {
        return $this->get(self::DATA_TYPE);
    }

    /**
     * Sets data-type of a value.
     */
    public function setDataType(string $dataType): void
    {
        $this->set(self::DATA_TYPE, $dataType);
    }

    /**
     * Checks whether a regular expression that can be used to validate a value exists.
     */
    public function hasRequirement(): bool
    {
        return null !== $this->requirement;
    }

    /**
     * Gets a regular expression that can be used to validate a value.
     */
    public function getRequirement(): ?string
    {
        return $this->requirement;
    }

    /**
     * Sets a regular expression that can be used to validate a value.
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
     */
    public function isArrayAllowed(): bool
    {
        return $this->arrayAllowed;
    }

    /**
     * Sets a flag determines if a value can be an array.
     */
    public function setArrayAllowed(bool $flag): void
    {
        $this->arrayAllowed = $flag;
    }

    /**
     * Gets a flag determines if a value can be a pair of "from" and "to" values.
     */
    public function isRangeAllowed(): bool
    {
        return $this->rangeAllowed;
    }

    /**
     * Sets a flag determines if a value can be a pair of "from" and "to" values.
     */
    public function setRangeAllowed(bool $flag): void
    {
        $this->rangeAllowed = $flag;
    }

    /**
     * Gets a delimiter that should be used to split a string to separate elements.
     */
    public function getArrayDelimiter(): string
    {
        return $this->arrayDelimiter;
    }

    /**
     * Sets a delimiter that should be used to split a string to separate elements.
     */
    public function setArrayDelimiter(string $delimiter): void
    {
        $this->arrayDelimiter = $delimiter;
    }

    /**
     * Gets a delimiter that should be used to split a string to a pair of "from" and "to" values.
     */
    public function getRangeDelimiter(): string
    {
        return $this->rangeDelimiter;
    }

    /**
     * Sets a delimiter that should be used to split a string to a pair of "from" and "to" values.
     */
    public function setRangeDelimiter(string $delimiter): void
    {
        $this->rangeDelimiter = $delimiter;
    }
}

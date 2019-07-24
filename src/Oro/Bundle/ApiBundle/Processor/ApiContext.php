<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\Context as BaseContext;

/**
 * The base execution context for Data API processors.
 */
abstract class ApiContext extends BaseContext
{
    /** the request type */
    const REQUEST_TYPE = 'requestType';

    /** API version */
    const VERSION = 'version';

    /** @var array[]|null */
    private $processed;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Sets default values into the context.
     */
    protected function initialize()
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
     * Marks a work as already done.
     * In the most cases this method is useless because it is easy to determine
     * when a work is already done by checking a state of a context.
     * However, a processor performs a complex work, it might be required
     * to mark a work as already done directly.
     *
     * @param string $operationName The name of an operation that represents some work
     */
    public function setProcessed($operationName)
    {
        $this->processed[$operationName] = true;
    }

    /**
     * Marks a work as not yet done.
     *
     * @param string $operationName The name of an operation that represents some work
     */
    public function clearProcessed($operationName)
    {
        if ($this->isProcessed($operationName)) {
            unset($this->processed[$operationName]);
        }
    }

    /**
     * Checks whether a work is already done.
     *
     * @param string $operationName The name of an operation that represents some work
     *
     * @return bool
     */
    public function isProcessed($operationName)
    {
        return
            null !== $this->processed
            && array_key_exists($operationName, $this->processed)
            && $this->processed[$operationName];
    }
}

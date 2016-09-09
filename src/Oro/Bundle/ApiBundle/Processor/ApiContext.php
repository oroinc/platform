<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\Context as BaseContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

abstract class ApiContext extends BaseContext
{
    /** the request type */
    const REQUEST_TYPE = 'requestType';

    /** API version */
    const VERSION = 'version';

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Sets default values into the Context.
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
}

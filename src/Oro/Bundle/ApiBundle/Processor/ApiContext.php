<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\Context as BaseContext;

abstract class ApiContext extends BaseContext
{
    /** the type of a request, for example "rest", "soap", "odata", etc. */
    const REQUEST_TYPE = 'requestType';

    /** API version */
    const VERSION = 'version';

    /**
     * Gets the type of a request, for example "rest", "soap", "odata", etc.
     *
     * @return string|null
     */
    public function getRequestType()
    {
        return $this->get(self::REQUEST_TYPE);
    }

    /**
     * Sets the type of a request, for example "rest", "soap", "odata", etc.
     *
     * @param string $requestType
     */
    public function setRequestType($requestType)
    {
        $this->set(self::REQUEST_TYPE, $requestType);
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

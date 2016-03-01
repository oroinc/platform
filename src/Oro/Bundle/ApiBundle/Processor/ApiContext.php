<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\Context as BaseContext;

abstract class ApiContext extends BaseContext
{
    /** a list of types the current request belongs, for example "rest", "soap", etc. */
    const REQUEST_TYPE = 'requestType';

    /** API version */
    const VERSION = 'version';

    /**
     * Gets a list of types the current request belongs, for example "rest", "soap", etc.
     * A request can belong to several types, e.g. "rest" and "json_api".
     *
     * @return string[]
     */
    public function getRequestType()
    {
        $requestTypes = $this->get(self::REQUEST_TYPE);

        return null !== $requestTypes
            ? $requestTypes
            : [];
    }

    /**
     * Sets the type of the current request, for example "rest", "soap", etc.
     * A request can belong to several types, e.g. "rest" and "json_api".
     * This method adds the given type(s) to a list of already set types.
     *
     * @param string|string[] $requestType
     */
    public function setRequestType($requestType)
    {
        $types = $this->getRequestType();
        foreach ((array)$requestType as $type) {
            if (!in_array($type, $types, true)) {
                $types[] = $type;
            }
        }
        $this->set(self::REQUEST_TYPE, $types);
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

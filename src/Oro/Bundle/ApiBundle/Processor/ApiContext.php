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

    /**
     * Gets the current request type.
     * A request can belong to several types, e.g. "rest" and "json_api".
     *
     * @return RequestType
     */
    public function getRequestType()
    {
        $requestTypes = $this->get(self::REQUEST_TYPE);

        return null !== $requestTypes
            ? $requestTypes
            : new RequestType([]);
    }

    /**
     * Sets the type of the current request, for example "rest", "soap", etc.
     * A request can belong to several types, e.g. "rest" and "json_api".
     * This method adds the given type(s) to a list of already set types.
     *
     * @param RequestType|string|string[] $requestType
     */
    public function setRequestType($requestType)
    {
        if ($requestType instanceof RequestType) {
            $this->set(self::REQUEST_TYPE, $requestType);
        } else {
            $type = $this->getRequestType();
            foreach ((array)$requestType as $item) {
                $type->add($item);
            }
            $this->set(self::REQUEST_TYPE, $type);
        }
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

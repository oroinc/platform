<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * Represents the type of Data API requests.
 */
class RequestType implements ToArrayInterface
{
    /**
     * REST API request
     */
    const REST = 'rest';

    /**
     * A request that conforms JSON API specification
     * @see http://jsonapi.org
     */
    const JSON_API = 'json_api';

    /** @var string[] */
    protected $types = [];

    /** @var string */
    private $str;

    /**
     * @param string[] $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function contains($type)
    {
        return in_array($type, $this->types, true);
    }

    /**
     * @param string $type
     */
    public function add($type)
    {
        if (!in_array($type, $this->types, true)) {
            $this->types[] = $type;
            $this->str     = null;
        }
    }

    /**
     * @param string $type
     */
    public function remove($type)
    {
        $key = array_search($type, $this->types, true);
        if (false !== $key) {
            unset($this->types[$key]);
            $this->types = array_values($this->types);
            $this->str   = null;
        }
    }

    /**
     * @param RequestType $requestType
     */
    public function set(RequestType $requestType)
    {
        $this->types = $requestType->types;
        $this->str   = $requestType->str;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->types);
    }

    public function clear()
    {
        $this->types = [];
        $this->str   = null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (null === $this->str) {
            $this->str = implode(',', $this->types);
        }

        return $this->str;
    }
}

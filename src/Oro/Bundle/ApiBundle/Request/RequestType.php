<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * Represents the type of Data API requests.
 */
class RequestType implements ToArrayInterface
{
    /**
     * REST API request.
     */
    public const REST = 'rest';

    /**
     * A request that conforms JSON API specification.
     * @link http://jsonapi.org
     */
    public const JSON_API = 'json_api';

    /** @var string[] */
    private $aspects = [];

    /** @var string */
    private $str;

    /**
     * @param string[] $aspects
     */
    public function __construct(array $aspects)
    {
        $this->aspects = $aspects;
    }

    /**
     * Checks whether this request type represents the given aspect.
     *
     * @param string $aspect
     *
     * @return bool
     */
    public function contains(string $aspect): bool
    {
        return \in_array($aspect, $this->aspects, true);
    }

    /**
     * Adds an aspect to this request type.
     *
     * @param string $aspect
     */
    public function add(string $aspect): void
    {
        if (!\in_array($aspect, $this->aspects, true)) {
            $this->aspects[] = $aspect;
            $this->str = null;
        }
    }

    /**
     * Adds an aspect from this request type.
     *
     * @param string $aspect
     */
    public function remove(string $aspect): void
    {
        $key = \array_search($aspect, $this->aspects, true);
        if (false !== $key) {
            unset($this->aspects[$key]);
            $this->aspects = \array_values($this->aspects);
            $this->str = null;
        }
    }

    /**
     * Initializes this request type based on an another request type.
     *
     * @param RequestType $requestType
     */
    public function set(RequestType $requestType): void
    {
        $this->aspects = $requestType->aspects;
        $this->str = $requestType->str;
    }

    /**
     * Checks if this request type represents at least one aspect.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->aspects);
    }

    /**
     * Removes all aspects from this request type.
     */
    public function clear(): void
    {
        $this->aspects = [];
        $this->str = null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->aspects;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (null === $this->str) {
            $aspects = $this->aspects;
            \rsort($aspects, SORT_STRING);
            $this->str = \implode(',', $aspects);
        }

        return $this->str;
    }
}

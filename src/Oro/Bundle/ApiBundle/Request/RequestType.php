<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * Represents the type of API requests.
 */
class RequestType implements ToArrayInterface
{
    /**
     * REST API request.
     */
    public const REST = 'rest';

    /**
     * A request that conforms JSON:API specification.
     * @link http://jsonapi.org
     */
    public const JSON_API = 'json_api';

    /**
     * Indicates that a request is the part of a batch request.
     */
    public const BATCH = 'batch';

    /** @var string[] */
    private array $aspects;
    private ?string $str = null;

    /**
     * @param string[] $aspects
     */
    public function __construct(array $aspects)
    {
        $this->aspects = $aspects;
    }

    /**
     * Checks whether this request type represents the given aspect.
     */
    public function contains(string $aspect): bool
    {
        return \in_array($aspect, $this->aspects, true);
    }

    /**
     * Adds an aspect to this request type.
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
     */
    public function remove(string $aspect): void
    {
        $key = array_search($aspect, $this->aspects, true);
        if (false !== $key) {
            unset($this->aspects[$key]);
            $this->aspects = array_values($this->aspects);
            $this->str = null;
        }
    }

    /**
     * Initializes this request type based on an another request type.
     */
    public function set(RequestType $requestType): void
    {
        $this->aspects = $requestType->aspects;
        $this->str = $requestType->str;
    }

    /**
     * Checks if this request type represents at least one aspect.
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
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->aspects;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        if (null === $this->str) {
            $aspects = $this->aspects;
            rsort($aspects, SORT_STRING);
            $this->str = implode(',', $aspects);
        }

        return $this->str;
    }
}

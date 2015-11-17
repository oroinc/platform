<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterBagInterface;

class Context extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** a query is used to get result data */
    const QUERY = 'query';

    /** the Criteria object is used to add additional restrictions to a query is used to get result data */
    const CRITERIA = 'criteria';

    /**
     * this header can be used to request additional data like "total count"
     * that will be returned in a response headers
     */
    const INCLUDE_HEADER = 'X-Include';

    /** @var ParameterBagInterface */
    private $requestHeaders;

    /** @var ParameterBagInterface */
    private $responseHeaders;

    /**
     * Gets headers an API request
     *
     * @return ParameterBagInterface
     */
    public function getRequestHeaders()
    {
        if (null === $this->requestHeaders) {
            $this->requestHeaders = new CaseInsensitiveParameterBag();
        }

        return $this->requestHeaders;
    }

    /**
     * Sets an object that will be used to accessing headers an API request
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setRequestHeaders(ParameterBagInterface $parameterBag)
    {
        $this->requestHeaders = $parameterBag;
    }

    /**
     * Gets headers an API response
     *
     * @return ParameterBagInterface
     */
    public function getResponseHeaders()
    {
        if (null === $this->responseHeaders) {
            $this->responseHeaders = new ParameterBag();
        }

        return $this->responseHeaders;
    }

    /**
     * Sets an object that will be used to accessing headers an API response
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setResponseHeaders(ParameterBagInterface $parameterBag)
    {
        $this->responseHeaders = $parameterBag;
    }

    /**
     * Gets FQCN of an entity
     *
     * @return string|null
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Checks whether a query is used to get result data exists
     *
     * @return bool
     */
    public function hasQuery()
    {
        return $this->has(self::QUERY);
    }

    /**
     * Gets a query is used to get result data
     *
     * @return mixed
     */
    public function getQuery()
    {
        return $this->get(self::QUERY);
    }

    /**
     * Sets a query is used to get result data
     *
     * @param mixed $query
     */
    public function setQuery($query)
    {
        $this->set(self::QUERY, $query);
    }

    /**
     * Gets the Criteria object is used to add additional restrictions to a query is used to get result data
     *
     * @return Criteria|null
     */
    public function getCriteria()
    {
        return $this->get(self::CRITERIA);
    }

    /**
     * Sets the Criteria object is used to add additional restrictions to a query is used to get result data
     *
     * @param Criteria $criteria
     */
    public function setCriteria($criteria)
    {
        $this->set(self::CRITERIA, $criteria);
    }
}

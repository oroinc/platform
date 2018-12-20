<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\RequestAwareFilterInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;

class RequestAwareFilterStub extends ComparisonFilter implements RequestAwareFilterInterface
{
    /** @var RequestType|null */
    private $requestType;

    /**
     * @return RequestType|null
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestType(RequestType $requestType)
    {
        $this->requestType = $requestType;
    }
}

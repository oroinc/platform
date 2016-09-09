<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;

class AbstractRestApiSubresourceController extends AbstractRestApiController
{
    /**
     * @param ActionProcessorInterface $processor
     * @param Request                  $request
     *
     * @return SubresourceContext
     */
    protected function getContext(ActionProcessorInterface $processor, Request $request)
    {
        /** @var SubresourceContext $context */
        $context = $processor->createContext();
        $context->getRequestType()->add(RequestType::REST);
        $context->setParentClassName($request->attributes->get('entity'));
        $context->setParentId($request->attributes->get('id'));
        $context->setAssociationName($request->attributes->get('association'));
        $context->setRequestHeaders(new RestRequestHeaders($request));

        return $context;
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class RestApiSubresourceController extends AbstractRestApiSubresourceController
{
    /**
     * Get an entity (for to-one association) or a list of entities (for to-many association)
     * connected to the given entity by the given association
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Get related entity or entities",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var GetSubresourceContext $context */
        $context = $this->getContext($processor, $request);
        $context->setFilterValues(new RestFilterValueAccessor($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }
}

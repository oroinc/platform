<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\AddRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\DeleteRelationship\DeleteRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\UpdateRelationship\UpdateRelationshipContext;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class RestApiRelationshipController extends AbstractRestApiSubresourceController
{
    /**
     * Get an entity identifier (for to-one association) or a list of entity identifiers (for to-many association)
     * connected to the given entity by the given association
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Get a relationship",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var GetRelationshipContext $context */
        $context = $this->getContext($processor, $request);
        $context->setFilterValues(new RestFilterValueAccessor($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Update a relationship between entities represented by the given association
     * For to-one association the target entity can be NULL to clear the association
     * For to-many association the existing relationships will be completely replaced with the specified list
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Update a relationship",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function patchAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var UpdateRelationshipContext $context */
        $context = $this->getContext($processor, $request);
        $context->setRequestData($request->request->all());

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Add the specified entities to the relationship represented by the given to-many association
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Add a relationship",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var AddRelationshipContext $context */
        $context = $this->getContext($processor, $request);
        $context->setRequestData($request->request->all());

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Delete the specified entities from the relationship represented by the given to-many association
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Delete a relationship",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var DeleteRelationshipContext $context */
        $context = $this->getContext($processor, $request);
        $context->setRequestData($request->request->all());

        $processor->process($context);

        return $this->buildResponse($context);
    }
}

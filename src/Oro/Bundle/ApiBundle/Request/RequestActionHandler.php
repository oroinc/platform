<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\AddRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\DeleteRelationship\DeleteRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\UpdateRelationship\UpdateRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Component\ChainProcessor\AbstractParameterBag;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for handling Data API actions.
 */
abstract class RequestActionHandler
{
    /** @var string[] */
    private $requestType;

    /** @var ActionProcessorBagInterface */
    private $actionProcessorBag;

    /**
     * @param string[]                    $requestType
     * @param ActionProcessorBagInterface $actionProcessorBag
     */
    public function __construct(array $requestType, ActionProcessorBagInterface $actionProcessorBag)
    {
        $this->requestType = $requestType;
        $this->actionProcessorBag = $actionProcessorBag;
    }

    /**
     * Handles "GET /api/{entity}/{id}" request,
     * that returns an entity by its identifier.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleGet(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET);
        /** @var GetContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $context->setId($this->getRequestParameter($request, 'id'));
        $context->setFilterValues($this->getRequestFilters($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "GET /api/{entity}" request,
     * that returns a list of entities.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleGetList(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET_LIST);
        /** @var GetListContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $context->setFilterValues($this->getRequestFilters($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "DELETE /api/{entity}/{id}" request,
     * that deletes an entity by its identifier.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleDelete(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::DELETE);
        /** @var DeleteContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $context->setId($this->getRequestParameter($request, 'id'));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "DELETE /api/{entity}" request,
     * that deletes a list of entities by the specified filter(s).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleDeleteList(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::DELETE_LIST);
        /** @var DeleteListContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $context->setFilterValues($this->getRequestFilters($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "POST /api/{entity}/{id}" request,
     * that creates a new entity.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleCreate(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::CREATE);
        /** @var CreateContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "PATCH /api/{entity}/{id}" request,
     * that updates an entity fields or associations.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleUpdate(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::UPDATE);
        /** @var UpdateContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $context->setId($this->getRequestParameter($request, 'id'));
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "GET /api/{entity}/{id}/{association}" request,
     * that returns an entity (for to-one association)
     * or a list of entities (for to-many association)
     * connected to the given entity by the given association.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleGetSubresource(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET_SUBRESOURCE);
        /** @var GetSubresourceContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setFilterValues($this->getRequestFilters($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "PATCH /api/{entity}/{id}/{association}" request,
     * that updates an entity (or entities, it depends on the association type) connected
     * to the given entity by the given association.
     * This type of the request is non-standard and do not have default implementation,
     * additional processors should be added for each association requires it.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleUpdateSubresource(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::UPDATE_SUBRESOURCE);
        /** @var ChangeSubresourceContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "POST /api/{entity}/{id}/{association}" request,
     * that adds an entity (or entities, it depends on the association type) connected
     * to the given entity by the given association.
     * This type of the request is non-standard and do not have default implementation,
     * additional processors should be added for each association requires it.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleAddSubresource(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::ADD_SUBRESOURCE);
        /** @var ChangeSubresourceContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "DELETE /api/{entity}/{id}/{association}" request,
     * that deletes an entity (or entities, it depends on the association type) connected
     * to the given entity by the given association.
     * This type of the request is non-standard and do not have default implementation,
     * additional processors should be added for each association requires it.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleDeleteSubresource(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::DELETE_SUBRESOURCE);
        /** @var ChangeSubresourceContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "GET /api/{entity}/{id}/relationships/{association}" request,
     * that returns an entity identifier (for to-one association)
     * or a list of entity identifiers (for to-many association)
     * connected to the given entity by the given association.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleGetRelationship(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET_RELATIONSHIP);
        /** @var GetRelationshipContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setFilterValues($this->getRequestFilters($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "PATCH /api/{entity}/{id}/relationships/{association}" request,
     * that updates a relationship between entities represented by the given association.
     * For to-one association the target entity can be NULL to clear the association.
     * For to-many association the existing relationships will be completely replaced with the specified list.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleUpdateRelationship(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::UPDATE_RELATIONSHIP);
        /** @var UpdateRelationshipContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "POST /api/{entity}/{id}/relationships/{association}" request,
     * that adds the specified entities to the relationship represented by the given to-many association
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleAddRelationship(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::ADD_RELATIONSHIP);
        /** @var AddRelationshipContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "DELETE /api/{entity}/{id}/relationships/{association}" request,
     * that deletes the specified entities from the relationship represented by the given to-many association
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleDeleteRelationship(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::DELETE_RELATIONSHIP);
        /** @var DeleteRelationshipContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $context->setRequestData($this->getRequestData($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "OPTIONS /api/{entity}/{id}" request,
     * that returns the communication options for the target resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleOptionsItem(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::OPTIONS);
        /** @var OptionsContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $this->prepareOptionsContext($context, $request, OptionsContext::ACTION_TYPE_ITEM);
        $context->setId($this->getRequestParameter($request, 'id'));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "OPTIONS /api/{entity}" request,
     * that returns the communication options for the target resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleOptionsList(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::OPTIONS);
        /** @var OptionsContext $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $this->prepareOptionsContext($context, $request, OptionsContext::ACTION_TYPE_LIST);

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "OPTIONS /api/{entity}/{id}/{association}" request,
     * that returns the communication options for the target resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleOptionsSubresource(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::OPTIONS);
        /** @var OptionsContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $this->prepareOptionsContext($context, $request, OptionsContext::ACTION_TYPE_SUBRESOURCE);

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles "OPTIONS /api/{entity}/{id}/relationships/{association}" request,
     * that returns the communication options for the target resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleOptionsRelationship(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::OPTIONS);
        /** @var OptionsContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $this->prepareOptionsContext($context, $request, OptionsContext::ACTION_TYPE_RELATIONSHIP);

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles not allowed "/api/{entity}/{id}" request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleNotAllowedItem(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET);
        /** @var Context $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $this->updateNotAllowedContextAction($context, 'item');

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles not allowed "/api/{entity}" request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleNotAllowedList(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET_LIST);
        /** @var Context $context */
        $context = $processor->createContext();
        $this->preparePrimaryContext($context, $request);
        $this->updateNotAllowedContextAction($context, 'list');

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles not allowed "/api/{entity}/{id}/{association}" request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleNotAllowedSubresource(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET_SUBRESOURCE);
        /** @var SubresourceContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $this->updateNotAllowedContextAction($context, 'subresource');

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Handles not allowed "/api/{entity}/{id}/relationships/{association}" request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleNotAllowedRelationship(Request $request): Response
    {
        $processor = $this->getProcessor(ApiActions::GET_RELATIONSHIP);
        /** @var SubresourceContext $context */
        $context = $processor->createContext();
        $this->prepareSubresourceContext($context, $request);
        $this->updateNotAllowedContextAction($context, 'relationship');

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * @param string $action
     *
     * @return ActionProcessorInterface
     */
    protected function getProcessor(string $action): ActionProcessorInterface
    {
        return $this->actionProcessorBag->getProcessor($action);
    }

    /**
     * @param Context $context
     * @param Request $request
     */
    protected function prepareContext(Context $context, Request $request): void
    {
        $requestType = $context->getRequestType();
        foreach ($this->requestType as $type) {
            $requestType->add($type);
        }
        $context->setMasterRequest(true);
        $context->setRequestHeaders($this->getRequestHeaders($request));
        $context->setHateoas(true);
    }

    /**
     * @param Context $context
     * @param Request $request
     */
    protected function preparePrimaryContext(Context $context, Request $request): void
    {
        $this->prepareContext($context, $request);
        $context->setClassName($this->getRequestParameter($request, 'entity'));
    }

    /**
     * @param SubresourceContext $context
     * @param Request            $request
     */
    protected function prepareSubresourceContext(SubresourceContext $context, Request $request): void
    {
        $this->prepareContext($context, $request);
        $context->setParentClassName($this->getRequestParameter($request, 'entity'));
        $context->setParentId($this->getRequestParameter($request, 'id'));
        $context->setAssociationName($this->getRequestParameter($request, 'association'));
    }

    /**
     * @param OptionsContext $context
     * @param Request        $request
     * @param string         $actionType
     */
    protected function prepareOptionsContext(OptionsContext $context, Request $request, string $actionType): void
    {
        $context->setActionType($actionType);
    }

    /**
     * @param Context $context
     * @param string  $actionType
     */
    protected function updateNotAllowedContextAction(Context $context, string $actionType): void
    {
        $context->set('actionType', $actionType);
        $context->setAction('not_allowed');
    }

    /**
     * @param Request $request
     * @param string  $attributeName
     *
     * @return mixed
     */
    protected function getRequestParameter(Request $request, string $attributeName)
    {
        return $request->attributes->get($attributeName);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestData(Request $request): array
    {
        return $request->request->all();
    }

    /**
     * @param Request $request
     *
     * @return AbstractParameterBag
     */
    abstract protected function getRequestHeaders(Request $request): AbstractParameterBag;

    /**
     * @param Request $request
     *
     * @return FilterValueAccessorInterface
     */
    abstract protected function getRequestFilters(Request $request): FilterValueAccessorInterface;

    /**
     * @param Context $context
     *
     * @return Response
     */
    abstract protected function buildResponse(Context $context): Response;
}

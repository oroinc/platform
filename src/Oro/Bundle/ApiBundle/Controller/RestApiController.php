<?php

namespace Oro\Bundle\ApiBundle\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\ActionProcessorBag;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class RestApiController extends FOSRestController
{
    /**
     * Get a list of entities
     *
     * @param Request $request
     *
     * @ApiDoc(description="Get entities", resource=true)
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var GetListContext $context */
        $context = $this->getContext($processor, $request);
        $context->setFilterValues(new RestFilterValueAccessor($request));

        $processor->process($context);

        return $this->buildGetResponse(
            $context,
            function ($result) {
                return is_array($result) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND;
            }
        );
    }

    /**
     * Get an entity
     *
     * @param Request $request
     *
     * @ApiDoc(description="Get entity", resource=true)
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var GetContext $context */
        $context = $this->getContext($processor, $request);
        $context->setId($request->attributes->get('id'));

        $processor->process($context);

        return $this->buildGetResponse(
            $context,
            function ($result) {
                return null !== $result ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND;
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return ActionProcessor
     */
    protected function getProcessor(Request $request)
    {
        /** @var ActionProcessorBag $processorBag */
        $processorBag = $this->get('oro_api.action_processor_bag');

        return $processorBag->getProcessor($request->attributes->get('_action'));
    }

    /**
     * @param ActionProcessor $processor
     * @param Request         $request
     *
     * @return Context
     */
    protected function getContext(ActionProcessor $processor, Request $request)
    {
        /** @var Context $context */
        $context = $processor->createContext();
        $context->setRequestType(RequestType::REST);
        $context->setVersion($request->attributes->get('version'));
        $context->setClassName($request->attributes->get('entity'));
        $context->setRequestHeaders(new RestRequestHeaders($request));

        return $context;
    }

    /**
     * @param Context  $context
     * @param callable $getStatusCode
     *
     * @return Response
     */
    protected function buildGetResponse(Context $context, $getStatusCode)
    {
        $result = $context->getResult();

        $view = $this->view($result, $getStatusCode($result));
        $serializationContext = new SerializationContext();
        $serializationContext->setSerializeNull(true);
        $view->setSerializationContext($serializationContext);
        $this->setResponseHeaders($view, $context);

        return $this->handleView($view);
    }

    /**
     * @param View    $view
     * @param Context $context
     */
    protected function setResponseHeaders(View $view, Context $context)
    {
        $headers = $context->getResponseHeaders()->toArray();
        foreach ($headers as $key => $value) {
            $view->setHeader($key, $value);
        }
    }
}

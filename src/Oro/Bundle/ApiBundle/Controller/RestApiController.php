<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
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
     * @ApiDoc(description="Get entities", resource=true, views={"rest_plain", "rest_json_api"})
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

        return $this->buildResponse($context);
    }

    /**
     * Get an entity
     *
     * @param Request $request
     *
     * @ApiDoc(description="Get entity", resource=true, views={"rest_plain", "rest_json_api"})
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var GetContext $context */
        $context = $this->getContext($processor, $request);
        $context->setId($request->attributes->get('id'));
        $context->setFilterValues(new RestFilterValueAccessor($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Delete an entity
     *
     * @param Request $request
     *
     * @ApiDoc(description="Delete entity", resource=true, views={"rest_plain", "rest_json_api"})
     *
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var DeleteContext $context */
        $context = $this->getContext($processor, $request);
        $context->setId($request->attributes->get('id'));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Delete a list of entities
     *
     * @param Request $request
     *
     * @ApiDoc(description="Delete entities", resource=true, views={"rest_plain", "rest_json_api"})
     *
     * @return Response
     */
    public function cdeleteAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var DeleteListContext $context */
        $context = $this->getContext($processor, $request);
        $context->setFilterValues(new RestFilterValueAccessor($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Update an entity
     *
     * @param Request $request
     *
     * @ApiDoc(description="Update an entity", resource=true, views={"rest_plain", "rest_json_api"})
     *
     * @return Response
     */
    public function patchAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var UpdateContext $context */
        $context = $this->getContext($processor, $request);
        $context->setId($request->attributes->get('id'));
        $context->setRequestData($request->request->all());

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Create an entity
     *
     * @param Request $request
     *
     * @ApiDoc(description="Create an entity", resource=true, views={"rest_plain", "rest_json_api"})
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var CreateContext $context */
        $context = $this->getContext($processor, $request);
        $context->setRequestData($request->request->all());

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * @param Request $request
     *
     * @return ActionProcessorInterface
     */
    protected function getProcessor(Request $request)
    {
        /** @var ActionProcessorBagInterface $processorBag */
        $processorBag = $this->get('oro_api.action_processor_bag');

        return $processorBag->getProcessor($request->attributes->get('_action'));
    }

    /**
     * @param ActionProcessorInterface $processor
     * @param Request                  $request
     *
     * @return Context
     */
    protected function getContext(ActionProcessorInterface $processor, Request $request)
    {
        /** @var Context $context */
        $context = $processor->createContext();
        $context->getRequestType()->add(RequestType::REST);
        $context->setClassName($request->attributes->get('entity'));
        $context->setRequestHeaders(new RestRequestHeaders($request));

        return $context;
    }

    /**
     * @param Context $context
     *
     * @return Response
     */
    protected function buildResponse(Context $context)
    {
        $view = $this->view($context->getResult());

        $view->setStatusCode($context->getResponseStatusCode() ?: Response::HTTP_OK);
        foreach ($context->getResponseHeaders()->toArray() as $key => $value) {
            $view->setHeader($key, $value);
        }

        // use custom handler because the response data are already normalized
        // and we do not need to additional processing of them
        /** @var ViewHandler $handler */
        $handler = $this->get('fos_rest.view_handler');
        $handler->registerHandler(
            'json',
            function (ViewHandler $viewHandler, View $view, Request $request, $format) {
                $response = $view->getResponse();
                $encoder = new JsonEncode();
                $response->setContent($encoder->encode($view->getData(), $format));
                if (!$response->headers->has('Content-Type')) {
                    $response->headers->set('Content-Type', $request->getMimeType($format));
                }

                return $response;
            }
        );

        return $handler->handle($view);
    }
}

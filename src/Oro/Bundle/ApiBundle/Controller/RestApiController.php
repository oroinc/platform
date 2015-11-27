<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBag;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class RestApiController extends FOSRestController
{
    const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

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
        $this->validateRequest($request);

        $processor = $this->getProcessor($request);
        /** @var GetListContext $context */
        $context = $this->getContext($processor, $request);
        $context->setFilterValues(new RestFilterValueAccessor($request));
        $context->getResponseHeaders()->set('Content-Type', self::JSON_API_CONTENT_TYPE);

        $processor->process($context);

        return $this->buildGetResponse(
            $context,
            function ($result) {
                return is_array($result) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND;
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
        $this->validateRequest($request);

        $processor = $this->getProcessor($request);
        /** @var GetContext $context */
        $context = $this->getContext($processor, $request);
        $context->setId($request->attributes->get('id'));
        $context->getResponseHeaders()->set('Content-Type', self::JSON_API_CONTENT_TYPE);

        $processor->process($context);

        return $this->buildGetResponse(
            $context,
            function ($result) {
                return null !== $result ? Response::HTTP_OK : Response::HTTP_NOT_FOUND;
            }
        );
    }

    /**
     * @param Request $request
     */
    protected function validateRequest(Request $request)
    {
        /**
         * Servers MUST respond with a 415 Unsupported Media Type status code
         *  if a request specifies the header Content-Type: application/vnd.api+json with any media type parameters.
         */
        $requestContentType = $request->headers->get('content-type');
        if (false !== strpos($requestContentType, ';')) {
            throw new UnsupportedMediaTypeHttpException(
                'Request\'s "Content-Type" header should not contain any media type parameters.'
            );
        }

        /**
         * Clients MUST send all JSON API data in request documents with the header
         *   Content-Type: application/vnd.api+json without any media type parameters.
         */
        if ($requestContentType !== self::JSON_API_CONTENT_TYPE) {
            throw new UnsupportedMediaTypeHttpException(
                'Unsupported Content-Type.'
            );
        }

        /**
         * Servers MUST respond with a 406 Not Acceptable status code if a request's Accept header contains only
         *   the JSON API media type and all instances of that media type are modified with media type parameters.
         */
        $acceptHeader = array_map('trim', explode(',', $request->headers->get('accept')));
        if (!array_intersect(['*/*', 'application/*', self::JSON_API_CONTENT_TYPE], $acceptHeader)) {
            throw new NotAcceptableHttpException(
                'Not supported "Accept" header or it contains the JSON API content type ' .
                'and all instances of that are modified with media type parameters.'
            );
        }
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
        $context->setRequestType(RequestType::REST_JSON_API);
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
        $view->getSerializationContext()->setSerializeNull(true);
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

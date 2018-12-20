<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\CorsHeaders;
use Oro\Bundle\ApiBundle\Request\RequestActionHandler as BaseRequestActionHandler;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessorFactory;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Oro\Component\ChainProcessor\AbstractParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * Handles API actions for REST API.
 */
class RequestActionHandler extends BaseRequestActionHandler
{
    /** @var ViewHandlerInterface */
    private $viewHandler;

    /** @var RestFilterValueAccessorFactory */
    private $filterValueAccessorFactory;

    /**
     * @param string[]                       $requestType
     * @param ActionProcessorBagInterface    $actionProcessorBag
     * @param RestFilterValueAccessorFactory $filterValueAccessorFactory
     * @param ViewHandlerInterface           $viewHandler
     */
    public function __construct(
        array $requestType,
        ActionProcessorBagInterface $actionProcessorBag,
        RestFilterValueAccessorFactory $filterValueAccessorFactory,
        ViewHandlerInterface $viewHandler
    ) {
        parent::__construct($requestType, $actionProcessorBag);
        $this->filterValueAccessorFactory = $filterValueAccessorFactory;
        $this->viewHandler = $viewHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestHeaders(Request $request): AbstractParameterBag
    {
        return new RestRequestHeaders($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestFilters(Request $request): FilterValueAccessorInterface
    {
        return $this->filterValueAccessorFactory->create($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareContext(Context $context, Request $request): void
    {
        parent::prepareContext($context, $request);
        if ($request->headers->has(CorsHeaders::ORIGIN)
            && $request->headers->get(CorsHeaders::ORIGIN) !== $request->getSchemeAndHttpHost()
        ) {
            $context->setCorsRequest(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function buildResponse(Context $context): Response
    {
        $view = View::create($context->getResult());

        $view->setStatusCode($context->getResponseStatusCode() ?: Response::HTTP_OK);
        foreach ($context->getResponseHeaders()->toArray() as $key => $value) {
            $view->setHeader($key, $value);
        }

        // use custom handler because the response data are already normalized
        // and we do not need to additional processing of them
        $this->viewHandler->registerHandler(
            'json',
            function (ViewHandlerInterface $viewHandler, View $view, Request $request, $format) {
                $response = $view->getResponse();
                $data = $view->getData();
                if (null !== $data) {
                    $encoder = new JsonEncode();
                    $response->setContent($encoder->encode($data, $format));
                } elseif (Response::HTTP_OK === $view->getStatusCode()) {
                    $response->headers->set('Content-Length', 0);
                }
                if (!$response->headers->has('Content-Type')) {
                    $response->headers->set('Content-Type', $request->getMimeType($format));
                }

                return $response;
            }
        );

        return $this->viewHandler->handle($view);
    }
}

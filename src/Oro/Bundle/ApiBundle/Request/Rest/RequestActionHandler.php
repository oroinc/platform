<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\RequestActionHandler as BaseRequestActionHandler;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Oro\Component\ChainProcessor\AbstractParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

class RequestActionHandler extends BaseRequestActionHandler
{
    /** @var ViewHandlerInterface */
    private $viewHandler;

    /**
     * @param string[]                    $requestType
     * @param ActionProcessorBagInterface $actionProcessorBag
     * @param ViewHandlerInterface        $viewHandler
     */
    public function __construct(
        array $requestType,
        ActionProcessorBagInterface $actionProcessorBag,
        ViewHandlerInterface $viewHandler
    ) {
        parent::__construct($requestType, $actionProcessorBag);
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
        return new RestFilterValueAccessor($request);
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
                $encoder = new JsonEncode();
                $response->setContent($encoder->encode($view->getData(), $format));
                if (!$response->headers->has('Content-Type')) {
                    $response->headers->set('Content-Type', $request->getMimeType($format));
                }

                return $response;
            }
        );

        return $this->viewHandler->handle($view);
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;

abstract class AbstractRestApiController extends FOSRestController
{
    /**
     * @return ActionProcessorBagInterface
     */
    protected function getActionProcessorBag()
    {
        return $this->get('oro_api.action_processor_bag');
    }

    /**
     * @param Request $request
     *
     * @return ActionProcessorInterface
     */
    protected function getProcessor(Request $request)
    {
        return $this->getActionProcessorBag()->getProcessor($request->attributes->get('_action'));
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

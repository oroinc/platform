<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\ApiBundle\Handler\ActionHandler;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
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
        $handler = $this->getActionHandler();
        $context = $this->getContext($handler, $request);

        $handler->handle($context);

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
        $handler = $this->getActionHandler();
        /** @var GetContext $context */
        $context = $this->getContext($handler, $request);
        $context->setId($request->attributes->get('id'));

        $handler->handle($context);

        return $this->buildGetResponse(
            $context,
            function ($result) {
                return null !== $result ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND;
            }
        );
    }

    /**
     * @return ActionHandler
     */
    protected function getActionHandler()
    {
        return $this->get('oro_api.action_handler');
    }

    /**
     * @param ActionHandler $handler
     * @param Request       $request
     *
     * @return Context
     */
    protected function getContext(ActionHandler $handler, Request $request)
    {
        $context = $handler->createContext($request->attributes->get('_action'));
        $context->setRequestType(RequestType::REST);
        $context->setVersion($request->attributes->get('version'));
        $context->setClassName($request->attributes->get('entity'));
        $context->setRequestHeaders(new RestRequestHeaders($request));
        $context->setFilterValues(new RestFilterValueAccessor($request));

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

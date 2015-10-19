<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\ApiBundle\Handler\ActionHandler;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

/**
 * @RouteResource("item")
 * @NamePrefix("oro_api_rest_")
 */
class RestApiController extends FOSRestController
{
    /**
     * Gets items
     *
     * @param Request $request The request
     * @param string  $version API version
     * @param string  $entity  The plural alias of an entity
     *
     * @Get("/{version}/{entity}", name="")
     *
     * @ApiDoc(description="Gets items", resource=true)
     *
     * @return Response
     */
    public function cgetAction(Request $request, $version, $entity)
    {
        $handler = $this->getHandler();

        /** @var GetListContext $context */
        $context = $handler->createContext('get_list');
        $context->setVersion($version);
        $context->setClassName($this->getEntityClassName($entity));
        $this->initRequestHeaders($request, $context);

        $handler->handle($context);

        $result = $context->getResult();

        $view = $this->view($result, is_array($result) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
        $this->setResponseHeaders($view, $context);

        return $this->handleView($view);
    }

    /**
     * Gets item
     *
     * @param Request $request The request
     * @param string  $version API version
     * @param string  $entity  The plural alias of an entity
     * @param string  $id      The identifier of an entity
     *
     * @Get("/{version}/{entity}/{id}", name="")
     *
     * @ApiDoc(description="Gets item", resource=true)
     *
     * @return Response
     */
    public function getAction(Request $request, $version, $entity, $id)
    {
        $handler = $this->getHandler();

        /** @var GetContext $context */
        $context = $handler->createContext('get');
        $context->setVersion($version);
        $context->setClassName($this->getEntityClassName($entity));
        $context->setId($id);
        $this->initRequestHeaders($request, $context);

        $handler->handle($context);

        $result = $context->getResult();

        $view = $this->view($result, null !== $result ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
        $this->setResponseHeaders($view, $context);

        return $this->handleView($view);
    }

    /**
     * @return ActionHandler
     */
    protected function getHandler()
    {
        return $this->get('oro_api.action_handler');
    }

    /**
     * @param string $pluralAlias
     *
     * @return string
     */
    protected function getEntityClassName($pluralAlias)
    {
        return $this->get('oro_entity.entity_alias_resolver')
            ->getClassByPluralAlias($pluralAlias);
    }

    /**
     * @param Request $request
     * @param Context $context
     */
    protected function initRequestHeaders(Request $request, Context $context)
    {
        $headers = $context->getRequestHeaders();
        $keys    = $request->headers->keys();
        foreach ($keys as $key) {
            $headers->set(str_replace('_', '-', $key), $request->headers->get($key));
        }
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

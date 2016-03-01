<?php

namespace Oro\Bundle\TranslationBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Handler\Context;

/**
 * @RouteResource("translation")
 * @NamePrefix("oro_api_")
 */
class TranslationController extends FOSRestController
{
    /**
     * Get translations.
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *      name="domain",
     *      requirements=".+",
     *      nullable=true,
     *      description="The translation domain. Defaults to 'messages'."
     * )
     * @QueryParam(
     *      name="locale",
     *      requirements=".+",
     *      nullable=true,
     *      description="The translation locale."
     * )
     * @ApiDoc(
     *      description="Get translations",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction()
    {
        $page   = (int)$this->getRequest()->get('page', 1);
        $limit  = (int)$this->getRequest()->get('limit', RestGetController::ITEMS_PER_PAGE);
        $domain = $this->getRequest()->get('domain', 'messages');

        $result = $this->get('translator')->getTranslations([$domain]);

        $data = [];
        if (isset($result[$domain]) && is_array($result[$domain])) {
            $slice = array_slice(
                $result[$domain],
                $page > 0 ? ($page - 1) * $limit : 0,
                $limit
            );
            foreach ($slice as $key => $val) {
                $data[] = ['key' => $key, 'value' => $val];
            }
        }

        $view     = $this->view($data, Codes::HTTP_OK);
        $response = parent::handleView($view);

        $responseContext = [
            'result'     => $data,
            'totalCount' => function () use ($result, $domain) {
                return isset($result[$domain]) && is_array($result[$domain])
                    ? count($result[$domain])
                    : 0;
            },
        ];

        $includeHandler = $this->get('oro_soap.handler.include');
        $includeHandler->handle(
            new Context(
                $this,
                $this->get('request'),
                $response,
                RestGetController::ACTION_LIST,
                $responseContext
            )
        );

        return $response;
    }
}

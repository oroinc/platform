<?php

namespace Oro\Bundle\TranslationBundle\Controller\Api\Rest;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Handler\Context;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;

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
     *      nullable=false,
     *      description="The translation domain."
     * )
     * @QueryParam(
     *      name="locale",
     *      requirements="^[a-z]{2}$",
     *      nullable=true,
     *      description="The preferred locale for translation. Default for all locales."
     * )
     * @ApiDoc(
     *      description="Get all translation",
     *      resource=true
     * )
     * @AclAncestor("oro_translation_view")
     * @return Response
     */
    public function cgetAction()
    {
        $page   = (int)$this->getRequest()->get('page', 1);
        $limit  = (int)$this->getRequest()->get('limit', RestGetController::ITEMS_PER_PAGE);
        $domain = $this->getRequest()->get('domain');

        $result = $this->get('translator.default')->getTranslations([$domain]);

        $totalCount = 0;
        $data = [$domain => []];
        if (isset($result[$domain]) && is_array($result[$domain])) {
            $data = [$domain => array_slice($result[$domain], ($page - 1) * $limit, $limit)];
            $totalCount = count($result[$domain]);
        }

        $view = $this->view($data, Codes::HTTP_OK);
        $response = parent::handleView($view);
        $values = [
            'totalCount' => function () use ($totalCount) {
                return $totalCount;
            },
        ];

        $includeHandler = $this->get('oro_soap.handler.include');
        $includeHandler->handle(
            new Context(
                $this,
                $this->get('request'),
                $response,
                RestApiReadInterface::ACTION_LIST,
                $values
            )
        );

        return $response;
    }
}

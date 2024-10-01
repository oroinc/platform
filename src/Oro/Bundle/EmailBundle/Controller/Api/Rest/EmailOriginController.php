<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for the email origins.
 */
class EmailOriginController extends RestGetController
{
    /**
     * Get user's email origins.
     *
     * @ApiDoc(
     *      description="Get user's email origins",
     *      resource=true
     * )
     * @param Request $request
     * @return Response
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. Defaults to 10.',
        nullable: true
    )]
    #[AclAncestor('oro_email_email_view')]
    public function cgetAction(Request $request)
    {
        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * Get user's email origin.
     *
     * @param string $id
     *
     * @ApiDoc(
     *      description="Get user's email origin",
     *      resource=true
     * )
     * @return Response
     */
    #[AclAncestor('oro_email_email_view')]
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_email.manager.email_origin.api');
    }
}

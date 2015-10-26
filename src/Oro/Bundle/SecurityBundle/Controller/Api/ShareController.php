<?php

namespace Oro\Bundle\SecurityBundle\Controller\Api;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @RouteResource("share")
 * @NamePrefix("oro_api_")
 */
class ShareController extends FOSRestController
{
    /**
     * @ApiDoc(
     *      description="Get search result for sharing entities",
     *      resource=true,
     *      filters={
     *          {"name"="entityClass", "dataType"="string"},
     *          {"name"="query", "dataType"="string"},
     *          {"name"="offset", "dataType"="integer"},
     *          {"name"="max_results", "dataType"="integer"}
     *      }
     * )
     *
     * @AclAncestor("oro_search")
     */
    public function getSharingEntitiesAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $results = $this->get('oro_security_search.index')->searchSharingEntities(
            $user,
            $this->getRequest()->get('entityClass'),
            $this->getRequest()->get('query')
        );

        return new Response(json_encode(['results' => $results]), Codes::HTTP_OK);
    }
}

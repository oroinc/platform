<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityApiEntityManager;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

/**
 * @RouteResource("activity_relation")
 * @NamePrefix("oro_api_")
 */
class ActivityEntityController extends RestGetController
{
    /**
     * Returns the list of entities associated with the specified activity entity.
     *
     * @Get("/activity_relations", name="")
     *
     * @QueryParam(
     *     name="activity_type",
     *     nullable=false,
     *     description="The type of the activity entity."
     * )
     * @QueryParam(
     *     name="activity_id",
     *     nullable=false,
     *     description="The id of the activity entity."
     * )
     *
     * @ApiDoc(
     *      description="Returns the list of entities associated with the specified activity entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction()
    {
        $manager = $this->getManager();
        $manager->setClass($this->getRequest()->get('activity_type'));

        $page     = (int)$this->getRequest()->get('page', 1);
        $limit    = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(
            [
                'id' => ['=', $this->getRequest()->get('activity_id')]
            ],
            [
                'id' => new IdentifierToReferenceFilter($this->getDoctrine(), $manager->getMetadata()->getName())
            ]
        );

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get entity manager
     *
     * @return ActivityEntityApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_entity.api');
    }
}

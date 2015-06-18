<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Post;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SoapBundle\Model\RelationIdentifier;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityApiEntityManager;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("activity_relation")
 * @NamePrefix("oro_api_")
 */
class ActivityEntityController extends RestController
{
    /**
     * Returns the list of entities associated with the specified activity entity.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @Get("/activities/{activity}/{id}/relations", name="")
     *
     * @ApiDoc(
     *      description="Returns the list of entities associated with the specified activity entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction($activity, $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(
            [
                'id' => ['=', $id]
            ],
            [
                'id' => new IdentifierToReferenceFilter($this->getDoctrine(), $manager->getMetadata()->getName())
            ]
        );

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Adds an association between an activity and an related entity.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @Post("/activities/{activity}/{id}/relations", name="")
     *
     * @ApiDoc(
     *      description="Adds an association between an activity and an related entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function postAction($activity, $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        return $this->handleUpdateRequest($id);
    }

    /**
     * Deletes an association between an activity and an related entity.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     * @param string $entity   The type of the related entity.
     * @param mixed  $entityId The id of the related entity.
     *
     * @Delete("/activities/{activity}/{id}/{entity}/{entityId}", name="")
     *
     * @ApiDoc(
     *      description="Deletes an association between an activity and an related entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function deleteAction($activity, $id, $entity, $entityId)
    {
        $manager       = $this->getManager();
        $activityClass = $manager->resolveEntityClass($activity, true);
        $manager->setClass($activityClass);

        $id = new RelationIdentifier(
            $activityClass,
            $id,
            $manager->resolveEntityClass($entity, true),
            $entityId
        );

        return $this->handleDeleteRequest($id);
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

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_activity.form.handler.activity_entity.api');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeleteHandler()
    {
        return $this->get('oro_activity.handler.delete.activity_entity');
    }
}

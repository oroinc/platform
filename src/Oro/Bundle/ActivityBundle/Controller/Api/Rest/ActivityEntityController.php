<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityApiEntityManager;
use Oro\Bundle\ActivityBundle\Exception\InvalidArgumentException;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("activity_relation")
 * @NamePrefix("oro_api_")
 */
class ActivityEntityController extends RestController
{
    /**
     * Get entities associated with the specified activity.
     *
     * @param Request $request
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @Get("/activities/{activity}/{id}/relations")
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
     *
     * @ApiDoc(
     *      description="Get entities associated with the specified activity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction(Request $request, $activity, $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(['id' => ['=', $id]]);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Adds an association between an activity and a target entity.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @Post("/activities/{activity}/{id}/relations")
     *
     * @ApiDoc(
     *      description="Adds an association between an activity and a target entity",
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
     * Deletes an association between an activity and a target entity.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     * @param string $entity   The type of the target entity.
     * @param mixed  $entityId The id of the target entity.
     *
     * @Delete("/activities/{activity}/{id}/{entity}/{entityId}")
     *
     * @ApiDoc(
     *      description="Deletes an association between an activity and a target entity",
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

        try {
            return $this->handleDeleteRequest($id);
        } catch (InvalidArgumentException $exception) {
            return $this->handleDeleteError($exception->getMessage(), Codes::HTTP_BAD_REQUEST, $id);
        } catch (\Exception $e) {
            return $this->handleDeleteError($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR, $id);
        }
    }

    /**
     * @param string             $message
     * @param int                $code
     * @param RelationIdentifier $id
     *
     * @return Response
     */
    protected function handleDeleteError($message, $code, RelationIdentifier $id)
    {
        $view = $this->view(['message' => $message], $code);
        return $this->buildResponse(
            $view,
            self::ACTION_DELETE,
            [
                'ownerEntityClass'  => $id->getOwnerEntityClass(),
                'ownerEntityId'     => $id->getOwnerEntityId(),
                'targetEntityClass' => $id->getTargetEntityClass(),
                'targetEntityId'    => $id->getTargetEntityId(),
                'success'           => false
            ]
        );
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
        return $this->get('oro_activity.handler.delete.activity_entity_proxy');
    }
}

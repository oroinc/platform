<?php

namespace Oro\Bundle\TagBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * API controller for taggable entities.
 */
class TaggableController extends RestController
{
    /**
     * Sets tags to the target entity and return them.
     *
     * @param string $entity   The type of the target entity.
     * @param int    $entityId The id of the target entity.
     *
     * @ApiDoc(
     *      description="Sets tags to the target entity and return them",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function postAction($entity, int $entityId)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($entity));

        $entity = $this->getManager()->find($entityId);

        if ($entity) {
            $entity = $this->processForm($entity);
            if ($entity) {
                $result = $this->get('oro_tag.tag.manager')->getPreparedArray($entity);

                // Returns tags for the updated entity.
                return $this->buildResponse(
                    ['tags' => $result],
                    self::ACTION_READ,
                    ['result' => $result],
                    Response::HTTP_OK
                );
            } else {
                $view = $this->view($this->getForm(), Response::HTTP_BAD_REQUEST);
            }
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $entityId, 'entity' => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_tag.tag.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_tag.form.handler.taggable.api');
    }
}

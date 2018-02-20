<?php

namespace Oro\Bundle\TagBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("taggable")
 * @NamePrefix("oro_api_")
 */
class TaggableController extends RestController
{
    /**
     * Sets tags to the target entity and return them.
     *
     * @param string $entity   The type of the target entity.
     * @param int    $entityId The id of the target entity.
     *
     * @Post("/tags/{entity}/{entityId}")
     *
     * @ApiDoc(
     *      description="Sets tags to the target entity and return them",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function postAction($entity, $entityId)
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
                    Codes::HTTP_OK
                );
            } else {
                $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
            }
        } else {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
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

<?php

namespace Oro\Bundle\TagBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Post;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("taggable")
 * @NamePrefix("oro_api_")
 */
class TaggableController extends RestController
{
    /**
     * Adds/updates tags to the target entity.
     *
     * @param string $entity   The type of the target entity.
     * @param int    $entityId The id of the target entity.
     *
     * @Post("/tags/{entity}/{entityId}")
     *
     * @ApiDoc(
     *      description="Adds tags to the target entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function postAction($entity, $entityId)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($entity));

        return $this->handleUpdateRequest($entityId);
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

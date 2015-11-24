<?php

namespace Oro\Bundle\TagBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Post;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("tag_entity")
 * @NamePrefix("oro_api_")
 */
class TagEntityController extends RestController
{
    /**
     * Adds tag to the target entity.
     *
     * @Post("/tags/{name}")
     *
     * @param string $name
     *
     * @ApiDoc(
     *      description="Adds tag to the target entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function postAction($name)
    {
        return $this->handleCreateRequest($name);
    }

    /**
     * Deletes tag from target entity.
     *
     * @param string $name     The name of the tag entity.
     * @param string $entity   The type of the target entity.
     * @param mixed  $entityId The id of the target entity.
     *
     * @Delete("/tags/{name}/{entity}/{entityId}")
     *
     * @Acl(
     *      id="oro_tag_delete",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="DELETE"
     * )
     *
     * @ApiDoc(
     *      description="Deletes tag from target entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function deleteAction($name, $entity, $entityId)
    {
        $id = new RelationIdentifier(
            null,
            $name,
            $this->getManager()->resolveEntityClass($entity, true),
            $entityId
        );

        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_tag.tag.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_tag.form.handler.tag_entity.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteHandler()
    {
        return $this->get('oro_tag.handler.tag_entity_delete.api');
    }
}

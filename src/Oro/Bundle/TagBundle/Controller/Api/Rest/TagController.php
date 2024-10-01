<?php

namespace Oro\Bundle\TagBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\TagBundle\Entity\Tag;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for Tag entity.
 */
class TagController extends RestController
{
    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete tag",
     *      resource=true
     * )
     * @return Response
     */
    #[Acl(id: 'oro_tag_delete', type: 'entity', class: Tag::class, permission: 'DELETE')]
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_tag.tag.manager.api');
    }

    #[\Override]
    public function getForm()
    {
        return $this->container->get('oro_tag.form.tag.api');
    }

    #[\Override]
    public function getFormHandler()
    {
        return $this->container->get('oro_tag.form.handler.api');
    }
}

<?php

namespace Oro\Bundle\AttachmentBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for Attachment entity.
 */
class AttachmentController extends RestController
{
    /**
     * Get attachment.
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Get attachment",
     *      resource=true
     * )
     *
     *
     * @return Response
     */
    #[AclAncestor('oro_attachment_view')]
    public function getAction(int $id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Delete attachment.
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete attachment",
     *      resource=true
     * )
     *
     *
     * @return Response
     */
    #[Acl(id: 'oro_attachment_delete', type: 'entity', class: Attachment::class, permission: 'DELETE')]
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_attachment.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }
}

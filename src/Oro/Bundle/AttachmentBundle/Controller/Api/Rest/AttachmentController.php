<?php

namespace Oro\Bundle\AttachmentBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
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
     * @AclAncestor("oro_attachment_view")
     *
     * @return Response
     */
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
     * @Acl(
     *      id="oro_attachment_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroAttachmentBundle:Attachment"
     * )
     *
     * @return Response
     */
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
        return $this->get('oro_attachment.manager.api');
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

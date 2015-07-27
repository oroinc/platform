<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("mailbox")
 * @NamePrefix("oro_api_")
 */
class MailboxController extends RestController
{
    /**
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete Mailbox",
     *      resource=true
     * )
     * @AclAncestor("oro_email_mailbox_delete")
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('This method is unsupported.');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_email.mailbox.manager.api');
    }
}

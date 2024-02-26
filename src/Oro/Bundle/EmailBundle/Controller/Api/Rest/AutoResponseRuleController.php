<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for AutoResponseRule entity.
 */
class AutoResponseRuleController extends RestController
{
    /**
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete Autoresponse Rule",
     *      resource=true
     * )
     * @return Response
     */
    #[Acl(
        id: 'oro_email_autoresponserule_delete',
        type: 'entity',
        class: AutoResponseRule::class,
        permission: 'DELETE'
    )]
    public function deleteAction(int $id)
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
        return $this->container->get('oro_email.manager.autoresponserule.api');
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Soap;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController;

class EmailController extends SoapGetController
{
    /**
     * @Soap\Method("getEmails")
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     * @Soap\Result(phpType = "Oro\Bundle\EmailBundle\Entity\Email[]")
     * @AclAncestor("oro_email_view")
     */
    public function cgetAction($page = 1, $limit = 10)
    {
        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * @Soap\Method("getEmail")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\EmailBundle\Entity\Email")
     * @AclAncestor("oro_email_view")
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * @Soap\Method("getEmailBody")
     * @Soap\Param("id", phpType="int")
     * @Soap\Result(phpType="Oro\Bundle\EmailBundle\Entity\EmailBody")
     * @AclAncestor("oro_email_view")
     */
    public function getEmailBodyAction($id)
    {
        /** @var Email $entity */
        $entity = $this->getEntity($id);
        $this->getEmailCacheManager()->ensureEmailBodyCached($entity);

        $result = $entity->getEmailBody();
        if (!$result) {
            throw new \SoapFault('NOT_FOUND', 'Email doesn\'t have body.');
        }
        return $result;
    }

    /**
     * @Soap\Method("getEmailAttachment")
     * @Soap\Param("id", phpType="int")
     * @Soap\Result(phpType="Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent")
     * @AclAncestor("oro_email_view")
     */
    public function getEmailAttachment($id)
    {
        return $this->getEmailAttachmentContentEntity($id);
    }

    /**
     * @Soap\Method("postAssociation")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Param("targetClassName", phpType = "string")
     * @Soap\Param("targetId", phpType = "int")
     * @Soap\Result(phpType = "boolean")
     * @AclAncestor("oro_email_edit")
     */
    public function postAssociationsAction($id, $targetClassName, $targetId)
    {
        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($id);
        $translator = $this->container->get('translator');

        if (!$entity) {
            throw new \SoapFault('NOT_FOUND', $translator->trans('oro.email.not_found', $id));
        }

        /**
         * @var $entityRoutingHelper EntityRoutingHelper
         */
        $entityRoutingHelper = $this->container->get('oro_entity.routing_helper');
        $targetClassName = $entityRoutingHelper->decodeClassName($targetClassName);

        if ($entity->supportActivityTarget($targetClassName)) {
            $target = $entityRoutingHelper->getEntity($targetClassName, $targetId);

            if (!$entity->hasActivityTarget($target)) {
                $this->container->get('oro_email.email.manager')->deleteContextFromEmailThread($entity, $target);
            } else {
                throw new \SoapFault('BAD_REQUEST', $translator->trans('oro.email.contexts.added.already'));
            }
        } else {
            throw new \SoapFault('BAD_REQUEST', $translator->trans('oro.email.contexts.type.not_supported'));
        }

        return $this->getManager()->getEntityId($entity);
    }

    /**
     * @Soap\Method("deleteAssociation")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Param("targetClassName", phpType = "string")
     * @Soap\Param("targetId", phpType = "int")
     * @Soap\Result(phpType = "boolean")
     * @AclAncestor("oro_email_edit")
     */
    public function deleteAssociationAction($id, $targetClassName, $targetId)
    {
        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($id);
        $translator = $this->container->get('translator');

        if (!$entity) {
            throw new \SoapFault('NOT_FOUND', $translator->trans('oro.email.not_found', $id));
        }

        $entityRoutingHelper = $this->container->get('oro_entity.routing_helper');
        $targetClassName = $entityRoutingHelper->decodeClassName($targetClassName);
        $target = $entityRoutingHelper->getEntity($targetClassName, $targetId);
        $this->container->get('oro_email.email.manager')->deleteContextFromEmailThread($entity, $target);

        return true;
    }

    /**
     * Get email attachment by identifier.
     *
     * @param integer $attachmentId
     * @return EmailAttachmentContent
     * @throws \SoapFault
     */
    protected function getEmailAttachmentContentEntity($attachmentId)
    {
        $attachment = $this->getManager()->findEmailAttachment($attachmentId);

        if (!$attachment) {
            throw new \SoapFault('NOT_FOUND', sprintf('Record #%u can not be found', $attachmentId));
        }

        return $attachment->getContent();
    }

    /**
     * Get entity manager
     *
     * @return EmailApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_email.manager.email.api');
    }

    /**
     * Get email cache manager
     *
     * @return EmailCacheManager
     */
    protected function getEmailCacheManager()
    {
        return $this->container->get('oro_email.email.cache.manager');
    }
}

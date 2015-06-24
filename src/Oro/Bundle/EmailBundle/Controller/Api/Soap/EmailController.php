<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Soap;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController;

class EmailController extends SoapGetController
{
    /**
     * @Soap\Method("getEmails")
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     * @Soap\Result(phpType = "Oro\Bundle\EmailBundle\Entity\Email[]")
     * @AclAncestor("oro_email_email_view")
     */
    public function cgetAction($page = 1, $limit = 10)
    {
        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function handleGetListRequest($page = 1, $limit = 10, $criteria = [], $orderBy = null)
    {
        $entities = array_filter(
            $this->getManager()->getList($limit, $page, $criteria, $orderBy),
            function ($entity) {
                return $this->container->get('oro_security.security_facade')->isGranted('VIEW', $entity);
            }
        );
        return $this->transformToSoapEntities($entities);
    }

    /**
     * @Soap\Method("getEmail")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\EmailBundle\Entity\Email")
     * @AclAncestor("oro_email_email_view")
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntity($id)
    {
        $entity = parent::getEntity($id);

        $this->assertEmailAccessGranted('VIEW', $entity);

        return $entity;
    }

    /**
     * @Soap\Method("getEmailBody")
     * @Soap\Param("id", phpType="int")
     * @Soap\Result(phpType="Oro\Bundle\EmailBundle\Entity\EmailBody")
     * @AclAncestor("oro_email_email_view")
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
     * @AclAncestor("oro_email_email_view")
     */
    public function getEmailAttachment($id)
    {
        return $this->getEmailAttachmentContentEntity($id);
    }

    /**
     * @param string $attribute
     * @param Email $entity
     *
     * @throws \SoapFault
     */
    protected function assertEmailAccessGranted($attribute, Email $entity)
    {
        if (!$this->container->get('oro_security.security_facade')->isGranted($attribute, $entity)) {
            throw new \SoapFault('FORBIDDEN', 'Record is forbidden');
        }
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
        $this->assertEmailAccessGranted('VIEW', $attachment->getEmailBody()->getEmail());

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

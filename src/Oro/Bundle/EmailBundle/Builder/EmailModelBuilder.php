<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;

/**
 * Class EmailModelBuilder
 *
 * @package Oro\Bundle\EmailBundle\Builder
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailModelBuilder
{
    /**
     * @var EmailModelBuilderHelper
     */
    protected $helper;
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EmailAttachmentProvider
     */
    protected $emailAttachmentProvider;

    /**
     * @var EmailActivityListProvider
     */
    protected $activityListProvider;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @param EmailModelBuilderHelper   $emailModelBuilderHelper
     * @param EntityManager             $entityManager
     * @param ConfigManager             $configManager
     * @param EmailActivityListProvider $activityListProvider
     * @param EmailAttachmentProvider   $emailAttachmentProvider
     * @param Factory                   $factory
     */
    public function __construct(
        EmailModelBuilderHelper $emailModelBuilderHelper,
        EntityManager $entityManager,
        ConfigManager $configManager,
        EmailActivityListProvider $activityListProvider,
        EmailAttachmentProvider $emailAttachmentProvider,
        Factory $factory
    ) {
        $this->helper               = $emailModelBuilderHelper;
        $this->entityManager        = $entityManager;
        $this->configManager        = $configManager;
        $this->activityListProvider = $activityListProvider;
        $this->emailAttachmentProvider = $emailAttachmentProvider;
        $this->factory = $factory;
    }

    /**
     * @param EmailModel $emailModel
     *
     * @return EmailModel
     */
    public function createEmailModel(EmailModel $emailModel = null)
    {
        if (!$emailModel) {
            $emailModel = $this->factory->getEmail();
            $emailModel->setMailType(EmailModel::MAIL_TYPE_DIRECT);
        }

        if ($this->request && $this->request->getMethod() === 'GET') {
            $this->applyRequest($emailModel);
            if (!$emailModel->getContexts()) {
                $entityClass = $this->request->get('entityClass');
                $entityId = $this->request->get('entityId');
                if ($entityClass && $entityId) {
                    $emailModel->setContexts([
                        $this->helper->getTargetEntity(
                            $this->request->get('entityClass'),
                            $this->request->get('entityId')
                        )
                    ]);
                }
            }
        }
        $this->applySignature($emailModel);
        $this->initAvailableAttachments($emailModel);

        return $emailModel;
    }

    /**
     * @param EmailEntity $parentEmailEntity
     *
     * @return EmailModel
     */
    public function createReplyEmailModel(EmailEntity $parentEmailEntity)
    {
        $emailModel = $this->factory->getEmail();
        $emailModel->setMailType(EmailModel::MAIL_TYPE_REPLY);
        $emailModel->setParentEmailId($parentEmailEntity->getId());

        $fromAddress = $parentEmailEntity->getFromEmailAddress();
        if ($fromAddress->getOwner() === $this->helper->getUser()) {
            $toEmail = $parentEmailEntity->getTo()->first()->getEmailAddress()->getEmail();
            $this->helper->preciseFullEmailAddress($toEmail);
            $emailModel->setTo([$toEmail]);
            $emailModel->setFrom($fromAddress->getEmail());
        } else {
            $toEmail = $fromAddress->getEmail();
            $this->helper->preciseFullEmailAddress($toEmail);
            $emailModel->setTo([$toEmail]);
            $this->initReplyFrom($emailModel, $parentEmailEntity);
        }

        $emailModel->setSubject($this->helper->prependWith('Re: ', $parentEmailEntity->getSubject()));

        $body = $this->helper->getEmailBody($parentEmailEntity, 'OroEmailBundle:Email/Reply:parentBody.html.twig');
        $emailModel->setBodyFooter($body);
        $emailModel->setContexts($this->activityListProvider->getTargetEntities($parentEmailEntity));

        return $this->createEmailModel($emailModel);
    }

    /**
     * @param EmailEntity $parentEmailEntity
     *
     * @return EmailModel
     */
    public function createReplyAllEmailModel(EmailEntity $parentEmailEntity)
    {
        $emailModel = $this->factory->getEmail();
        $emailModel->setMailType(EmailModel::MAIL_TYPE_REPLY);
        $emailModel->setParentEmailId($parentEmailEntity->getId());

        $fromAddress = $parentEmailEntity->getFromEmailAddress();
        if ($fromAddress->getOwner() === $this->helper->getUser()) {
            $toList = [];
            foreach ($parentEmailEntity->getTo() as $toRecipient) {
                $toEmail = $toRecipient->getEmailAddress()->getEmail();
                $this->helper->preciseFullEmailAddress($toEmail);
                $toList[] = $toEmail;
            }
            $ccList = [];
            foreach ($parentEmailEntity->getCc() as $ccRecipient) {
                $toEmail = $ccRecipient->getEmailAddress()->getEmail();
                $this->helper->preciseFullEmailAddress($toEmail);
                $ccList[] = $toEmail;
            }


            $emailModel->setTo($toList);
            $emailModel->setCc($ccList);
            $emailModel->setFrom($fromAddress->getEmail());
        } else {
            $toEmail = $fromAddress->getEmail();
            $this->helper->preciseFullEmailAddress($toEmail);
            $emailModel->setTo([$toEmail]);
            $this->initReplyAllFrom($emailModel, $parentEmailEntity);
        }

        $emailModel->setSubject($this->helper->prependWith('Re: ', $parentEmailEntity->getSubject()));

        $body = $this->helper->getEmailBody($parentEmailEntity, 'OroEmailBundle:Email/Reply:parentBody.html.twig');
        $emailModel->setBodyFooter($body);
        $emailModel->setContexts($this->activityListProvider->getTargetEntities($parentEmailEntity));

        return $this->createEmailModel($emailModel);
    }

    /**
     * @param EmailEntity $parentEmailEntity
     *
     * @return EmailModel
     */
    public function createForwardEmailModel(EmailEntity $parentEmailEntity)
    {
        $emailModel = $this->factory->getEmail();
        $emailModel->setMailType(EmailModel::MAIL_TYPE_FORWARD);
        $emailModel->setParentEmailId($parentEmailEntity->getId());

        $emailModel->setSubject($this->helper->prependWith('Fwd: ', $parentEmailEntity->getSubject()));
        $body = $this->helper->getEmailBody($parentEmailEntity, 'OroEmailBundle:Email/Forward:parentBody.html.twig');
        $emailModel->setBodyFooter($body);
        // link attachments of forwarded email to current email instance
        if ($this->request->isMethod('GET')) {
            $this->applyAttachments($emailModel, $parentEmailEntity);
        }

        return $this->createEmailModel($emailModel);
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @param EmailModel  $emailModel
     * @param EmailEntity $parentEmailEntity
     */
    protected function initReplyFrom(EmailModel $emailModel, EmailEntity $parentEmailEntity)
    {
        $user = $this->helper->getUser();
        if (!$user) {
            return;
        }

        $userEmails = $user->getEmails();
        if ($userEmails instanceof Collection) {
            $userEmails = $userEmails->toArray();
        }
        $mailboxes = $this->helper->getMailboxes();
        $userEmails = array_merge((array)$mailboxes, (array)$userEmails);
        $toEmails = [];
        $emailRecipients = $parentEmailEntity->getTo();
        /** @var EmailRecipient $emailRecipient */
        foreach ($emailRecipients as $emailRecipient) {
            $toEmails[] = $emailRecipient->getEmailAddress()->getEmail();
        }

        foreach ($userEmails as $userEmail) {
            if (in_array($userEmail->getEmail(), $toEmails, true)) {
                $emailModel->setFrom($userEmail->getEmail());
                break;
            }
        }
    }

    /**
     * @param EmailModel  $emailModel
     * @param EmailEntity $parentEmailEntity
     */
    protected function initReplyAllFrom(EmailModel $emailModel, EmailEntity $parentEmailEntity)
    {
        $userEmails = $this->helper->getUser()->getEmails();
        $toEmails = [];
        $ccEmails = [];
        $emailRecipients = $parentEmailEntity->getTo();
        $emailCcRecipients = $parentEmailEntity->getCc();
        /** @var EmailRecipient $emailRecipient */
        foreach ($emailRecipients as $emailRecipient) {
            $toEmails[] = $emailRecipient->getEmailAddress()->getEmail();
        }

        /** @var EmailRecipient $emailCcRecipient */
        foreach ($emailCcRecipients as $emailCcRecipient) {
            $ccEmails[] = $emailCcRecipient->getEmailAddress()->getEmail();
        }
        $emailModel->setCc($ccEmails);

        foreach ($userEmails as $userEmail) {
            if (in_array($userEmail->getEmail(), $toEmails, true)) {
                $emailModel->setFrom($userEmail->getEmail());
                break;
            }
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyRequest(EmailModel $emailModel)
    {
        $this->applyEntityData($emailModel);
        $this->applyFrom($emailModel);
        $this->applySubject($emailModel);
        $this->applyFrom($emailModel);
        $this->applyRecipients($emailModel);
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyEntityData(EmailModel $emailModel)
    {
        if ($this->request->query->has('entityClass')) {
            $emailModel->setEntityClass(
                $this->helper->decodeClassName($this->request->query->get('entityClass'))
            );
        }
        if ($this->request->query->has('entityId')) {
            $emailModel->setEntityId($this->request->query->get('entityId'));
        }
        if (!$emailModel->getEntityClass() || !$emailModel->getEntityId()) {
            if ($emailModel->getParentEmailId()) {
                $parentEmail = $this->entityManager->getRepository('OroEmailBundle:Email')
                    ->find($emailModel->getParentEmailId());
                $this->applyEntityDataFromEmail($emailModel, $parentEmail);
            }
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyFrom(EmailModel $emailModel)
    {
        if (!$emailModel->getFrom()) {
            if ($this->request->query->has('from')) {
                $from = $this->request->query->get('from');
                if (!empty($from)) {
                    $this->helper->preciseFullEmailAddress($from);
                }
                $emailModel->setFrom($from);
            } else {
                $user = $this->helper->getUser();
                if ($user) {
                    $emailModel->setFrom($this->helper->buildFullEmailAddress($user));
                }
            }
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyRecipients(EmailModel $emailModel)
    {
        $emailModel->setTo(
            array_merge($emailModel->getTo(), $this->getRecipients($emailModel, EmailRecipient::TO, true))
        );
        $emailModel->setCc(array_merge($emailModel->getCc(), $this->getRecipients($emailModel, EmailRecipient::CC)));
        $emailModel->setBcc(array_merge($emailModel->getBcc(), $this->getRecipients($emailModel, EmailRecipient::BCC)));
    }

    /**
     * @param EmailModel $emailModel
     * @param string $type
     * @param bool $excludeCurrentUser
     *
     * @return array
     */
    protected function getRecipients(EmailModel $emailModel, $type, $excludeCurrentUser = false)
    {
        $addresses = [];
        if ($this->request->query->has($type)) {
            $address = trim($this->request->query->get($type));
            if (!empty($address)) {
                $this->helper->preciseFullEmailAddress(
                    $address,
                    $emailModel->getEntityClass(),
                    $emailModel->getEntityId(),
                    $excludeCurrentUser
                );
            }
            if ($address) {
                $addresses = [$address];
            }
        }
        return $addresses;
    }

    /**
     * @param EmailModel $model
     */
    protected function applySubject(EmailModel $model)
    {
        if ($this->request->query->has('subject')) {
            $subject = trim($this->request->query->get('subject'));
            $model->setSubject($subject);
        }
    }

    /**
     * @param EmailModel  $emailModel
     * @param EmailEntity $emailEntity
     */
    protected function applyEntityDataFromEmail(EmailModel $emailModel, EmailEntity $emailEntity)
    {
        $entities = $emailEntity->getActivityTargetEntities();
        foreach ($entities as $entity) {
            if ($entity != $this->helper->getUser()) {
                $emailModel->setEntityClass(ClassUtils::getClass($entity));
                $emailModel->setEntityId($entity->getId());

                return;
            }
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applySignature(EmailModel $emailModel)
    {
        $signature = $this->configManager->get('oro_email.signature');
        if ($signature) {
            $emailModel->setSignature($signature);
        }
    }

    /**
     * @param EmailModel  $emailModel
     * @param EmailEntity $emailEntity
     */
    protected function applyAttachments(EmailModel $emailModel, EmailEntity $emailEntity)
    {
        try {
            $this->helper->ensureEmailBodyCached($emailEntity);

            foreach ($emailEntity->getEmailBody()->getAttachments() as $attachment) {
                $attachmentModel = $this->factory->getEmailAttachment();
                $attachmentModel->setId($attachment->getId());
                $attachmentModel->setType(EmailAttachment::TYPE_EMAIL_ATTACHMENT);
                $attachmentModel->setEmailAttachment($attachment);

                $emailModel->addAttachment($attachmentModel);
            }
        } catch (\Exception $e) {
            // maybe show notice to a user that attachments could not be loaded
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function initAvailableAttachments(EmailModel $emailModel)
    {
        $attachments = [];

        if ($emailModel->getParentEmailId()) {
            $parentEmail = $this->entityManager->getRepository('OroEmailBundle:Email')
                ->find($emailModel->getParentEmailId());
            $threadAttachments = $this->emailAttachmentProvider->getThreadAttachments($parentEmail);
            $threadAttachments = $this->filterAttachmentsByName($threadAttachments);
            $attachments = array_merge($attachments, $threadAttachments);
        }
        if ($emailModel->getEntityClass() && $emailModel->getEntityId()) {
            $scopeEntity = $this->entityManager->getRepository($emailModel->getEntityClass())
                ->find($emailModel->getEntityId());

            if ($scopeEntity) {
                $scopeEntityAttachments = $this->emailAttachmentProvider->getScopeEntityAttachments($scopeEntity);
                $scopeEntityAttachments = $this->filterAttachmentsByName($scopeEntityAttachments);
                $attachments = array_merge($attachments, $scopeEntityAttachments);
            }
        }

        $emailModel->setAttachmentsAvailable($attachments);
    }

    /**
     * @param array $attachments
     *
     * @return array
     */
    protected function filterAttachmentsByName($attachments)
    {
        $collection = new ArrayCollection($attachments);
        $fileNames = [];

        $filtered = $collection->filter(function ($entry) use (&$fileNames) {
            /** @var EmailAttachment $entry */
            if (in_array($entry->getFileName(), $fileNames)) {
                return false;
            } else {
                $fileNames[] = $entry->getFileName();

                return true;
            }
        });

        return $filtered->toArray();
    }
}

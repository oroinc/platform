<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds an email model base on the current request.
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
     * @var RequestStack
     */
    protected $requestStack;

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
     * @var HtmlTagHelper
     */
    private $htmlTagHelper;

    /**
     * @var FileConstraintsProvider
     */
    private $fileConstraintsProvider;

    public function __construct(
        EmailModelBuilderHelper $emailModelBuilderHelper,
        EntityManager $entityManager,
        ConfigManager $configManager,
        EmailActivityListProvider $activityListProvider,
        EmailAttachmentProvider $emailAttachmentProvider,
        Factory $factory,
        RequestStack $requestStack,
        HtmlTagHelper $htmlTagHelper,
        FileConstraintsProvider $fileConstraintsProvider
    ) {
        $this->helper = $emailModelBuilderHelper;
        $this->entityManager = $entityManager;
        $this->configManager = $configManager;
        $this->activityListProvider = $activityListProvider;
        $this->emailAttachmentProvider = $emailAttachmentProvider;
        $this->factory = $factory;
        $this->requestStack = $requestStack;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->fileConstraintsProvider = $fileConstraintsProvider;
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

        $request = $this->request ?? $this->requestStack->getCurrentRequest();
        if ($request) {
            $this->applyRequest($emailModel);
            if (!count($emailModel->getContexts())) {
                $entityClass = $request->get('entityClass');
                $entityId = $request->get('entityId');
                if ($entityClass && $entityId) {
                    $emailModel->setContexts([
                        $this->helper->getTargetEntity(
                            $entityClass,
                            $entityId
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

        $body = $this->helper->getEmailBody($parentEmailEntity, '@OroEmail/Email/Reply/parentBody.html.twig');
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

        $body = $this->helper->getEmailBody($parentEmailEntity, '@OroEmail/Email/Reply/parentBody.html.twig');
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
        $body = $this->helper->getEmailBody($parentEmailEntity, '@OroEmail/Email/Forward/parentBody.html.twig');
        $emailModel->setBodyFooter($body);
        // link attachments of forwarded email to current email instance
        $request = $this->request ?? $this->requestStack->getCurrentRequest();
        if ($request && $request->isMethod('GET')) {
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

    protected function applyRequest(EmailModel $emailModel)
    {
        $this->applyEntityData($emailModel);
        $this->applySubject($emailModel);
        $this->applyFrom($emailModel);
        $this->applyRecipients($emailModel);
    }

    protected function applyEntityData(EmailModel $emailModel)
    {
        $request = $this->request ?? $this->requestStack->getCurrentRequest();

        $entityClass = $request->get('entityClass');
        if ($entityClass) {
            $emailModel->setEntityClass(
                $this->helper->decodeClassName($entityClass)
            );
        }

        $entityId = $request->get('entityId');
        if ($entityId) {
            $emailModel->setEntityId($entityId);
        }
        if (!$emailModel->getEntityClass() || !$emailModel->getEntityId()) {
            if ($emailModel->getParentEmailId()) {
                $parentEmail = $this->entityManager->getRepository('OroEmailBundle:Email')
                    ->find($emailModel->getParentEmailId());
                $this->applyEntityDataFromEmail($emailModel, $parentEmail);
            }
        }
    }

    protected function applyFrom(EmailModel $emailModel)
    {
        if (!$emailModel->getFrom()) {
            $request = $this->request ?? $this->requestStack->getCurrentRequest();

            $from = $request->get('from');
            if ($from) {
                $this->helper->preciseFullEmailAddress($from);
            } else {
                $user = $this->helper->getUser();
                if ($user) {
                    $from = $this->helper->buildFullEmailAddress($user);
                }
            }

            $emailModel->setFrom($from);
        }
    }

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
        $request = $this->request ?? $this->requestStack->getCurrentRequest();
        $address = trim((string)$request->get($type, ''));
        if ($address) {
            $this->helper->preciseFullEmailAddress(
                $address,
                $emailModel->getEntityClass(),
                $emailModel->getEntityId(),
                $excludeCurrentUser
            );
        }

        return $address ? [$address] : [];
    }

    protected function applySubject(EmailModel $model)
    {
        $request = $this->request ?? $this->requestStack->getCurrentRequest();
        $subject = $request->get('subject');
        $subject = $subject ? trim($subject) : '';
        if ($subject) {
            $model->setSubject($subject);
        }
    }

    protected function applyEntityDataFromEmail(EmailModel $emailModel, EmailEntity $emailEntity)
    {
        $entities = $emailEntity->getActivityTargets();
        foreach ($entities as $entity) {
            if ($entity != $this->helper->getUser()) {
                $emailModel->setEntityClass(ClassUtils::getClass($entity));
                $emailModel->setEntityId($entity->getId());

                return;
            }
        }
    }

    protected function applySignature(EmailModel $emailModel)
    {
        $signature = $this->htmlTagHelper->sanitize($this->configManager->get('oro_email.signature'));
        if ($signature) {
            $emailModel->setSignature($signature);
        }
    }

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

    protected function initAvailableAttachments(EmailModel $emailModel)
    {
        $attachments = [];

        if ($emailModel->getParentEmailId()) {
            $parentEmail = $this->entityManager->getRepository(EmailEntity::class)
                ->find($emailModel->getParentEmailId());
            $threadAttachments = $this->emailAttachmentProvider->getThreadAttachments($parentEmail);
            $threadAttachments = $this->filterAttachmentsByName($threadAttachments);
            $attachments = $threadAttachments;
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

        $attachments = $this->filterAttachmentsByEmailMaxFileSize($attachments);
        $attachments = $this->filterAttachmentsByMimeTypes($attachments);

        $emailModel->setAttachmentsAvailable($attachments);
    }

    /**
     * @param array $attachments
     *
     * @return array
     */
    protected function filterAttachmentsByName($attachments)
    {
        $fileNames = [];

        return array_filter(
            $attachments,
            static function (EmailAttachment $entry) use (&$fileNames) {
                if (\in_array($entry->getFileName(), $fileNames, true)) {
                    return false;
                }

                $fileNames[] = $entry->getFileName();

                return true;
            }
        );
    }

    private function filterAttachmentsByEmailMaxFileSize(array $attachments): array
    {
        $maxFileSize = (float)$this->fileConstraintsProvider->getMaxSizeByConfigPath('oro_email.attachment_max_size');
        if ($maxFileSize === 0.0) {
            return $attachments;
        }

        return array_filter(
            $attachments,
            static function (EmailAttachment $entry) use ($maxFileSize) {
                return $entry->getFileSize() <= $maxFileSize;
            }
        );
    }

    private function filterAttachmentsByMimeTypes(array $attachments): array
    {
        $mimeTypes = $this->fileConstraintsProvider->getMimeTypes();

        return array_filter(
            $attachments,
            static function (EmailAttachment $entry) use ($mimeTypes) {
                return \in_array($entry->getMimeType(), $mimeTypes, true);
            }
        );
    }
}

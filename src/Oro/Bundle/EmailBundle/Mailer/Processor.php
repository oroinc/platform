<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

/**
 * Class Processor
 *
 * @package Oro\Bundle\EmailBundle\Mailer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Processor
{
    /** @var EntityManager */
    protected $em;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /** @var  EmailOwnerProvider */
    protected $emailOwnerProvider;

    /** @var  EmailActivityManager */
    protected $emailActivityManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var array */
    protected $origins = [];

    /** @var Mcrypt */
    protected $encryptor;

    /**
     * @param DoctrineHelper           $doctrineHelper
     * @param \Swift_Mailer            $mailer
     * @param EmailAddressHelper       $emailAddressHelper
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param EmailOwnerProvider       $emailOwnerProvider
     * @param EmailActivityManager     $emailActivityManager
     * @param ServiceLink              $serviceLink
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        \Swift_Mailer $mailer,
        EmailAddressHelper $emailAddressHelper,
        EmailEntityBuilder $emailEntityBuilder,
        EmailOwnerProvider $emailOwnerProvider,
        EmailActivityManager $emailActivityManager,
        ServiceLink $serviceLink,
        EventDispatcherInterface $eventDispatcher,
        Mcrypt $encryptor
    ) {
        $this->doctrineHelper       = $doctrineHelper;
        $this->mailer               = $mailer;
        $this->emailAddressHelper   = $emailAddressHelper;
        $this->emailEntityBuilder   = $emailEntityBuilder;
        $this->emailOwnerProvider   = $emailOwnerProvider;
        $this->emailActivityManager = $emailActivityManager;
        $this->securityFacade       = $serviceLink->getService();
        $this->eventDispatcher      = $eventDispatcher;
        $this->encryptor            = $encryptor;
    }

    /**
     * Process email model sending.
     *
     * @param EmailModel $model
     *
     * @return EmailUser
     * @throws \Swift_SwiftException
     */
    public function process(EmailModel $model)
    {
        $this->assertModel($model);
        $messageDate     = new \DateTime('now', new \DateTimeZone('UTC'));
        $parentMessageId = $this->getParentMessageId($model);

        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        if ($parentMessageId) {
            $message->getHeaders()->addTextHeader('References', $parentMessageId);
            $message->getHeaders()->addTextHeader('In-Reply-To', $parentMessageId);
        }
        $message->setDate($messageDate->getTimestamp());
        $message->setFrom($this->getAddresses($model->getFrom()));
        $message->setTo($this->getAddresses($model->getTo()));
        $message->setCc($this->getAddresses($model->getCc()));
        $message->setBcc($this->getAddresses($model->getBcc()));
        $message->setSubject($model->getSubject());
        $message->setBody($model->getBody(), $model->getType() === 'html' ? 'text/html' : 'text/plain');

        $this->addAttachments($message, $model);
        $this->processEmbeddedImages($message, $model);

        $messageId = '<' . $message->generateId() . '>';

        $origin = $this->getEmailOrigin($model->getFrom());
        $this->processSend($message, $origin);

        $emailUser = $this->emailEntityBuilder->emailUser(
            $model->getSubject(),
            $model->getFrom(),
            $model->getTo(),
            $messageDate,
            $messageDate,
            $messageDate,
            Email::NORMAL_IMPORTANCE,
            $model->getCc(),
            $model->getBcc(),
            $origin->getOwner(),
            $origin->getOrganization()
        );

        $emailUser->setFolder($origin->getFolder(FolderType::SENT));
        $emailUser->getEmail()->setEmailBody(
            $this->emailEntityBuilder->body($message->getBody(), $model->getType() === 'html', true)
        );
        $emailUser->getEmail()->setMessageId($messageId);
        $emailUser->setSeen(true);
        if ($parentMessageId) {
            $emailUser->getEmail()->setRefs($parentMessageId);
        }

        // persist the email and all related entities such as folders, email addresses etc.
        $this->emailEntityBuilder->getBatch()->persist($this->getEntityManager());
        $this->persistAttachments($model, $emailUser->getEmail());

        // associate the email with the target entity if exist
        $contexts = $model->getContexts();
        foreach ($contexts as $context) {
            $this->emailActivityManager->addAssociation($emailUser->getEmail(), $context);
        }

        // flush all changes to the database
        $this->getEntityManager()->flush();

        $event = new EmailBodyAdded($emailUser->getEmail());
        $this->eventDispatcher->dispatch(EmailBodyAdded::NAME, $event);

        return $emailUser;
    }

    /**
     * Process send email message. In case exist custom smtp host/port use it
     *
     * @param object $message
     * @param object $emailOrigin
     * @throws \Swift_SwiftException
     */
    public function processSend($message, $emailOrigin)
    {
        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->modifySmtpSettings($emailOrigin);
        }

        if (!$this->mailer->send($message)) {
            throw new \Swift_SwiftException('An email was not delivered.');
        }
    }

    /**
     * Modify transport smtp settings
     *
     * @param UserEmailOrigin $userEmailOrigin
     */
    protected function modifySmtpSettings(UserEmailOrigin $userEmailOrigin)
    {
        $transport = $this->mailer->getTransport();

        if ($transport instanceof \Swift_Transport_EsmtpTransport) {
            $transport->setHost($userEmailOrigin->getSmtpHost());
            $transport->setPort($userEmailOrigin->getSmtpPort());
            $transport->setUsername($userEmailOrigin->getUser());
            $transport->setPassword($this->encryptor->decryptData($userEmailOrigin->getPassword()));
            $transport->setEncryption($userEmailOrigin->getSsl());
        }
    }

    /**
     * Process inline images. Convert it to embedded attachments and update message body.
     *
     * @param \Swift_Message $message
     * @param EmailModel     $model
     */
    protected function processEmbeddedImages(\Swift_Message $message, EmailModel $model)
    {
        if ($model->getType() === 'html') {
            $guesser = ExtensionGuesser::getInstance();
            $body    = $message->getBody();
            $body    = preg_replace_callback(
                '/<img(.*)src(\s*)=(\s*)["\'](.*)["\']/U',
                function ($matches) use ($message, $guesser, $model) {
                    if (count($matches) === 5) {
                        // 1st match contains any data between '<img' and 'src' parts (e.g. 'width=100')
                        $imgConfig = $matches[1];

                        // 4th match contains src attribute value
                        $srcData = $matches[4];

                        if (strpos($srcData, 'data:image') === 0) {
                            list($mime, $content) = explode(';', $srcData);
                            list($encoding, $file) = explode(',', $content);
                            $mime            = str_replace('data:', '', $mime);
                            $fileName        = sprintf('%s.%s', uniqid(), $guesser->guess($mime));
                            $swiftAttachment = \Swift_Image::newInstance(
                                ContentDecoder::decode($file, $encoding),
                                $fileName,
                                $mime
                            );

                            /** @var $message \Swift_Message */
                            $id = $message->embed($swiftAttachment);

                            $attachmentContent = new EmailAttachmentContent();
                            $attachmentContent->setContent($file);
                            $attachmentContent->setContentTransferEncoding($encoding);

                            $emailAttachment = new EmailAttachment();
                            $emailAttachment->setEmbeddedContentId($swiftAttachment->getId());
                            $emailAttachment->setFileName($fileName);
                            $emailAttachment->setContentType($mime);
                            $attachmentContent->setEmailAttachment($emailAttachment);
                            $emailAttachment->setContent($attachmentContent);

                            $emailAttachmentModel = new EmailAttachmentModel();
                            $emailAttachmentModel->setEmailAttachment($emailAttachment);
                            $model->addAttachment($emailAttachmentModel);

                            return sprintf('<img%ssrc="%s"', $imgConfig, $id);
                        }
                    }
                },
                $body
            );
            $message->setBody($body, 'text/html');
        }
    }

    /**
     * @param \Swift_Message $message
     * @param EmailModel     $model
     */
    protected function addAttachments(\Swift_Message $message, EmailModel $model)
    {
        /** @var EmailAttachmentModel $emailAttachmentModel */
        foreach ($model->getAttachments() as $emailAttachmentModel) {
            $attachment      = $emailAttachmentModel->getEmailAttachment();
            $swiftAttachment = new \Swift_Attachment(
                ContentDecoder::decode(
                    $attachment->getContent()->getContent(),
                    $attachment->getContent()->getContentTransferEncoding()
                ),
                $attachment->getFileName(),
                $attachment->getContentType()
            );
            $message->attach($swiftAttachment);
        }
    }

    /**
     * @param EmailModel $model
     * @param Email      $email
     */
    protected function persistAttachments(EmailModel $model, Email $email)
    {
        /** @var EmailAttachmentModel $emailAttachmentModel */
        foreach ($model->getAttachments() as $emailAttachmentModel) {
            $attachment = $emailAttachmentModel->getEmailAttachment();

            if (!$attachment->getId()) {
                $this->getEntityManager()->persist($attachment);
            } else {
                $attachmentContent = clone $attachment->getContent();
                $attachment        = clone $attachment;
                $attachment->setContent($attachmentContent);
                $this->getEntityManager()->persist($attachment);
            }

            $email->getEmailBody()->addAttachment($attachment);
            $attachment->setEmailBody($email->getEmailBody());
        }
    }

    /**
     * Find existing email origin entity by email string or create and persist new one.
     *
     * @param string $email
     * @param string $originName
     *
     * @return EmailOrigin
     */
    public function getEmailOrigin($email, $originName = InternalEmailOrigin::BAP)
    {
        $originKey    = $originName . $email;
        $organization = $this->securityFacade !== null && $this->securityFacade->getOrganization()
            ? $this->securityFacade->getOrganization()
            : null;
        if (!array_key_exists($originKey, $this->origins)) {
            $emailOwner = $this->emailOwnerProvider->findEmailOwner(
                $this->getEntityManager(),
                $this->emailAddressHelper->extractPureEmailAddress($email)
            );

            if ($emailOwner instanceof User) {
                $origins = $emailOwner->getEmailOrigins()->filter(
                    function ($item) use ($organization) {
                        return
                            $item instanceof UserEmailOrigin && $item->isActive() && $item->isSmtpConfigured()
                            && (!$organization || $item->getOrganization() === $organization);
                    }
                );

                if ($origins->isEmpty()) {
                    $origins = $emailOwner->getEmailOrigins()->filter(
                        function ($item) use ($organization) {
                            return
                                $item instanceof InternalEmailOrigin
                                && (!$organization || $item->getOrganization() === $organization);
                        }
                    );
                }

                $origin = $origins->isEmpty() ? null : $origins->first();
                if ($origin === null) {
                    $origin = $this->createUserInternalOrigin($emailOwner, $organization);
                }
            } else {
                $origin = $this->getEntityManager()
                    ->getRepository('OroEmailBundle:InternalEmailOrigin')
                    ->findOneBy(['internalName' => $originName]);
            }
            $this->origins[$originKey] = $origin;
        }

        return $this->origins[$originKey];
    }

    /**
     * @param User                  $emailOwner
     * @param OrganizationInterface $organization
     *
     * @return InternalEmailOrigin
     */
    protected function createUserInternalOrigin(User $emailOwner, OrganizationInterface $organization = null)
    {
        $organization = $organization
            ? $organization
            : $emailOwner->getOrganization();
        $originName   = InternalEmailOrigin::BAP . '_User_' . $emailOwner->getId();

        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(FolderType::SENT)
            ->setName(FolderType::SENT)
            ->setFullName(FolderType::SENT);

        $origin = new InternalEmailOrigin();
        $origin
            ->setName($originName)
            ->addFolder($outboxFolder)
            ->setOwner($emailOwner)
            ->setOrganization($organization);

        $emailOwner->addEmailOrigin($origin);

        $this->getEntityManager()->persist($origin);
        $this->getEntityManager()->persist($emailOwner);

        return $origin;
    }

    /**
     * @param EmailModel $model
     *
     * @throws \InvalidArgumentException
     */
    protected function assertModel(EmailModel $model)
    {
        if (!$model->getFrom()) {
            throw new \InvalidArgumentException('Sender can not be empty');
        }
        if (!$model->getTo() && !$model->getCc() && !$model->getBcc()) {
            throw new \InvalidArgumentException('Recipient can not be empty');
        }
    }

    /**
     * Converts emails addresses to a form acceptable to \Swift_Mime_Message class
     *
     * @param string|string[] $addresses Examples of correct email addresses: john@example.com, <john@example.com>,
     *                                   John Smith <john@example.com> or "John Smith" <john@example.com>
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getAddresses($addresses)
    {
        $result = [];

        if (is_string($addresses)) {
            $addresses = [$addresses];
        }
        if (!is_array($addresses) && !$addresses instanceof \Iterator) {
            throw new \InvalidArgumentException(
                'The $addresses argument must be a string or a list of strings (array or Iterator)'
            );
        }

        foreach ($addresses as $address) {
            $name = $this->emailAddressHelper->extractEmailAddressName($address);
            if (empty($name)) {
                $result[] = $this->emailAddressHelper->extractPureEmailAddress($address);
            } else {
                $result[$this->emailAddressHelper->extractPureEmailAddress($address)] = $name;
            }
        }

        return $result;
    }

    /**
     * @param EmailModel $model
     *
     * @return string
     */
    protected function getParentMessageId(EmailModel $model)
    {
        $messageId     = '';
        $parentEmailId = $model->getParentEmailId();
        if ($parentEmailId && $model->getMailType() == EmailModel::MAIL_TYPE_REPLY) {
            $parentEmail = $this->getEntityManager()
                ->getRepository('OroEmailBundle:Email')
                ->find($parentEmailId);
            $messageId   = $parentEmail->getMessageId();
        }
        return $messageId;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->doctrineHelper->getEntityManager('OroEmailBundle:Email');
        }

        return $this->em;
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

/**
 * Class Processor
 *
 * @package Oro\Bundle\EmailBundle\Mailer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Processor
{
    /** @var EntityManager */
    protected $em;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /** @var DirectMailer */
    protected $mailer;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var  EmailActivityManager */
    protected $emailActivityManager;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var Mcrypt */
    protected $encryptor;

    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    /**
     * @param DoctrineHelper           $doctrineHelper
     * @param DirectMailer             $mailer
     * @param EmailAddressHelper       $emailAddressHelper
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param EmailActivityManager     $emailActivityManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param Mcrypt                   $encryptor
     * @param EmailOriginHelper        $emailOriginHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        DirectMailer $mailer,
        EmailAddressHelper $emailAddressHelper,
        EmailEntityBuilder $emailEntityBuilder,
        EmailActivityManager $emailActivityManager,
        EventDispatcherInterface $eventDispatcher,
        Mcrypt $encryptor,
        EmailOriginHelper $emailOriginHelper
    ) {
        $this->doctrineHelper       = $doctrineHelper;
        $this->mailer               = $mailer;
        $this->emailAddressHelper   = $emailAddressHelper;
        $this->emailEntityBuilder   = $emailEntityBuilder;
        $this->emailActivityManager = $emailActivityManager;
        $this->eventDispatcher      = $eventDispatcher;
        $this->encryptor            = $encryptor;
        $this->emailOriginHelper    = $emailOriginHelper;
    }

    /**
     * Process email model sending.
     *
     * @param EmailModel $model
     * @param EmailOrigin $origin Origin to send email with
     * @param bool $persist
     *
     * @return EmailUser
     * @throws \Swift_SwiftException
     */
    public function process(EmailModel $model, $origin = null, $persist = true)
    {
        $this->assertModel($model);
        $messageDate     = new \DateTime('now', new \DateTimeZone('UTC'));
        $parentMessageId = $this->getParentMessageId($model);
        $message = $this->prepareMessage($model, $parentMessageId, $messageDate);

        $this->processSend($message, $origin);

        $emailUser = $this->prepareEmailUser($model, $origin, $message, $messageDate, $parentMessageId);

        if ($persist) {
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
        }

        $event = new EmailBodyAdded($emailUser->getEmail());
        $this->eventDispatcher->dispatch(EmailBodyAdded::NAME, $event);

        return $emailUser;
    }

    /**
     * @param EmailModel  $model
     * @param \DateTime   $messageDate
     * @param EmailOrigin $origin
     *
     * @return EmailUser
     */
    protected function createEmailUser(EmailModel $model, $messageDate, $origin)
    {
        $owner = null;
        $organization = null;
        if ($origin) {
            $owner = $origin->getOwner();
            $organization = $origin->getOrganization();
        }
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
            $owner,
            $organization
        );
        if ($origin) {
            $emailUser->setOrigin($origin);
            if ($origin instanceof UserEmailOrigin) {
                if ($origin->getMailbox() !== null) {
                    $emailUser->setOwner(null);
                    $emailUser->setMailboxOwner($origin->getMailbox());
                }
            }
        }

        return $emailUser;
    }

    /**
     * Get origin's folder
     *
     * @param string $email
     * @param EmailOrigin $origin
     * @return EmailFolder
     */
    protected function getFolder($email, EmailOrigin $origin)
    {
        $folder = $origin->getFolder(FolderType::SENT);

        //In case when 'useremailorigin' origin doesn't have folder, get folder from internal origin
        if (!$folder && $origin instanceof UserEmailOrigin) {
            $origin = $this->emailOriginHelper->getEmailOrigin($email, null, InternalEmailOrigin::BAP, false);
            return $origin->getFolder(FolderType::SENT);
        }

        return $folder;
    }

    /**
     * Process send email message. In case exist custom smtp host/port use it
     *
     * @param \Swift_Message  $message
     * @param EmailOrigin $emailOrigin
     * @throws \Swift_SwiftException
     */
    public function processSend($message, $emailOrigin)
    {
        if ($emailOrigin && $emailOrigin instanceof UserEmailOrigin) {
            /* Modify transport smtp settings */
            if ($emailOrigin->isSmtpConfigured()) {
                $this->mailer->prepareSmtpTransport($emailOrigin);
            }
        }

        if (!$this->mailer->send($message)) {
            throw new \Swift_SwiftException('An email was not delivered.');
        }
    }

    /**
     * Process inline images. Convert it to embedded attachments and update message body.
     *
     * @param \Swift_Message  $message
     * @param EmailModel|null $model
     */
    public function processEmbeddedImages(\Swift_Message $message, EmailModel $model = null)
    {
        if ($model ? $model->getType() !== 'html' : $message->getContentType() !== 'text/html') {
            return;
        }

        $guesser = ExtensionGuesser::getInstance();
        $body = preg_replace_callback(
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

                        if ($model) {
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
                        }

                        return sprintf('<img%ssrc="%s"', $imgConfig, $id);
                    }
                }

                return $matches[0];
            },
            $message->getBody()
        );
        $message->setBody($body);
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
     * @deprecated since 1.9. Use {@see Oro\Bundle\EmailBundle\Tools\EmailOriginHelper} instead
     *
     * @param string                $email
     * @param OrganizationInterface $organization
     * @param string                $originName
     * @param boolean               $enableUseUserEmailOrigin
     *
     * @return EmailOrigin
     */
    public function getEmailOrigin(
        $email,
        OrganizationInterface $organization = null,
        $originName = InternalEmailOrigin::BAP,
        $enableUseUserEmailOrigin = true
    ) {
        return $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName, $enableUseUserEmailOrigin);
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

    /**
     * @param EmailModel $model
     * @param string $parentMessageId
     * @param \DateTime $messageDate
     *
     * @return \Swift_Message
     */
    protected function prepareMessage(EmailModel $model, $parentMessageId, $messageDate)
    {
        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        if ($parentMessageId) {
            $message->getHeaders()->addTextHeader('References', $parentMessageId);
            $message->getHeaders()->addTextHeader('In-Reply-To', $parentMessageId);
        }
        $addresses = $this->getAddresses($model->getFrom());
        $address = $this->emailAddressHelper->extractPureEmailAddress($model->getFrom());
        $message->setDate($messageDate->getTimestamp());
        $message->setFrom($addresses);
        $message->setReplyTo($addresses);
        $message->setReturnPath($address);
        $message->setTo($this->getAddresses($model->getTo()));
        $message->setCc($this->getAddresses($model->getCc()));
        $message->setBcc($this->getAddresses($model->getBcc()));
        $message->setSubject($model->getSubject());
        $message->setBody($model->getBody(), $model->getType() === 'html' ? 'text/html' : 'text/plain');

        $this->addAttachments($message, $model);
        $this->processEmbeddedImages($message, $model);

        return $message;
    }

    /**
     * @param EmailModel $model
     * @param EmailOrigin $origin
     * @param \Swift_Message $message
     * @param \DateTime $messageDate
     * @param string $parentMessageId
     *
     * @return EmailUser
     */
    protected function prepareEmailUser(EmailModel $model, $origin, $message, $messageDate, $parentMessageId)
    {
        $messageId = '<' . $message->generateId() . '>';
        $emailUser = $this->createEmailUser($model, $messageDate, $origin);
        if ($origin) {
            $emailUser->addFolder($this->getFolder($model->getFrom(), $origin));
        }
        $emailUser->getEmail()->setEmailBody(
            $this->emailEntityBuilder->body($message->getBody(), $model->getType() === 'html', true)
        );
        $emailUser->getEmail()->setMessageId($messageId);
        $emailUser->setSeen(true);
        if ($parentMessageId) {
            $emailUser->getEmail()->setRefs($parentMessageId);

            return $emailUser;
        }

        return $emailUser;
    }
}

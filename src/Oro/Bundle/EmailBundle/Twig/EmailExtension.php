<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailExtension extends \Twig_Extension
{
    const NAME = 'oro_email';

    /** @var EmailHolderHelper */
    protected $emailHolderHelper;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var EmailAttachmentManager */
    protected $emailAttachmentManager;

    /** @var EntityManager */
    protected $em;

    /**  @var MailboxProcessStorage */
    private $mailboxProcessStorage;

    /** @var SecurityFacade */
    private $securityFacade;

    /**
     * @param EmailHolderHelper      $emailHolderHelper
     * @param EmailAddressHelper     $emailAddressHelper
     * @param EmailAttachmentManager $emailAttachmentManager
     * @param EntityManager          $em
     * @param MailboxProcessStorage  $mailboxProcessStorage
     * @param SecurityFacade         $securityFacade
     */
    public function __construct(
        EmailHolderHelper $emailHolderHelper,
        EmailAddressHelper $emailAddressHelper,
        EmailAttachmentManager $emailAttachmentManager,
        EntityManager $em,
        MailboxProcessStorage $mailboxProcessStorage,
        SecurityFacade $securityFacade
    ) {
        $this->emailHolderHelper = $emailHolderHelper;
        $this->emailAddressHelper = $emailAddressHelper;
        $this->emailAttachmentManager = $emailAttachmentManager;
        $this->em = $em;
        $this->mailboxProcessStorage = $mailboxProcessStorage;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_get_email', [$this, 'getEmail']),
            new \Twig_SimpleFunction('oro_get_email_address_name', [$this, 'getEmailAddressName']),
            new \Twig_SimpleFunction('oro_get_email_address', [$this, 'getEmailAddress']),
            new \Twig_SimpleFunction('oro_get_email_thread_recipients', [$this, 'getEmailThreadRecipients']),
            new \Twig_SimpleFunction('oro_get_email_thread_attachments', [$this, 'getEmailThreadAttachments']),
            new \Twig_SimpleFunction('oro_can_attache', [$this, 'canReAttach']),
            new \Twig_SimpleFunction('oro_get_mailbox_process_label', [$this, 'getMailboxProcessLabel']),
            new \Twig_SimpleFunction('oro_get_email_clank_event', [$this, 'getEmailClankEvent']),
            new \Twig_SimpleFunction('oro_get_unread_emails_count', [$this, 'getUnreadEmailsCount'])
        ];
    }

    /**
     * Gets the email address of the given object
     *
     * @param object $object
     * @return string The email address or empty string if the object has no email
     */
    public function getEmail($object)
    {
        $result = $this->emailHolderHelper->getEmail($object);

        return null !== $result
            ? $result
            : '';
    }

    /**
     * Gets the recipients of the given thread
     *
     * @param EmailThread $thread
     * @return EmailRecipient[]
     */
    public function getEmailThreadRecipients($thread)
    {
        $result = $this->em->getRepository("OroEmailBundle:EmailRecipient")->getThreadUniqueRecipients($thread);

        return $result;
    }

    /**
     * Gets the attachments of the given thread
     *
     * @param EmailThread $thread
     * @return EmailAttachment[]
     */
    public function getEmailThreadAttachments($thread)
    {
        $result = $this->em->getRepository("OroEmailBundle:EmailAttachment")->getThreadAttachments($thread);

        return $result;
    }

    /**
     * Gets the email address name
     *
     * @param string $email
     * @return string The email address name or empty string if the name is not found
     */
    public function getEmailAddressName($email)
    {
        $result = $this->emailAddressHelper->extractEmailAddressName($email);

        return null !== $result
            ? $result
            : '';
    }

    /**
     * Gets the email address
     *
     * @param string $email
     * @return string The email address or empty string if the address is not found
     */
    public function getEmailAddress($email)
    {
        $result = $this->emailAddressHelper->extractPureEmailAddress($email);

        return null !== $result
            ? $result
            : '';
    }

    /**
     * Check possibility of reattach
     *
     * @param EmailAttachment $emailAttachment
     * @param object $targetEntity
     *
     * @return bool
     */
    public function canReAttach($emailAttachment, $targetEntity)
    {
        if ($this->emailAttachmentManager
                ->validateEmailAttachmentForTargetClass(
                    $emailAttachment,
                    ClassUtils::getRealClass($targetEntity)
                )->count() > 0
            || $this->emailAttachmentManager->isAttached($emailAttachment, $targetEntity)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return unique identificator for clank event. This identification
     * is used in notification widget to show message about new emails
     *
     * @return string
     */
    public function getEmailClankEvent()
    {
        if (!$this->securityFacade->hasLoggedUser()) {
            return '';
        }

        $currentOrganization = $this->securityFacade->getOrganization();
        $currentUser         = $this->securityFacade->getLoggedUser();

        return WebSocketSendProcessor::getUserTopic($currentUser, $currentOrganization);
    }

    /**
     * Return array of numbers unread emails per folder
     *
     * @return array
     */
    public function getUnreadEmailsCount()
    {
        if (!$this->securityFacade->hasLoggedUser()) {
            return [];
        }

        $currentOrganization = $this->securityFacade->getOrganization();
        $currentUser = $this->securityFacade->getLoggedUser();
        $result = $this->em->getRepository("OroEmailBundle:Email")
            ->getCountNewEmailsPerFolders($currentUser, $currentOrganization);
        $total = $this->em->getRepository("OroEmailBundle:Email")
            ->getCountNewEmails($currentUser, $currentOrganization);
        $result[] = array('num' => $total, 'id' => 0);

        return $result;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getMailboxProcessLabel($type)
    {
        return $this->mailboxProcessStorage->getProcess($type)->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

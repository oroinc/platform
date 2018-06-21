<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailExtension extends \Twig_Extension
{
    const NAME = 'oro_email';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return EmailHolderHelper
     */
    protected function getEmailHolderHelper()
    {
        return $this->container->get('oro_email.email_holder_helper');
    }

    /**
     * @return EmailAddressHelper
     */
    protected function getEmailAddressHelper()
    {
        return $this->container->get('oro_email.email.address.helper');
    }

    /**
     * @return EmailAttachmentManager
     */
    protected function getEmailAttachmentManager()
    {
        return $this->container->get('oro_email.manager.email_attachment_manager');
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

    /**
     * @return MailboxProcessStorage
     */
    protected function getMailboxProcessStorage()
    {
        return $this->container->get('oro_email.mailbox.process_storage');
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return $this->container->get('security.authorization_checker');
    }

    /**
     * @return TokenAccessorInterface
     */
    protected function getTokenAccessor()
    {
        return $this->container->get('oro_security.token_accessor');
    }

    /**
     * @return RelatedEmailsProvider
     */
    protected function getRelatedEmailsProvider()
    {
        return $this->container->get('oro_email.related_emails.provider');
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getDoctrine()->getManagerForClass($entityClass)->getRepository($entityClass);
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
            new \Twig_SimpleFunction('oro_get_email_ws_event', [$this, 'getEmailWSChannel']),
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
        $result = $this->getEmailHolderHelper()->getEmail($object);
        if (!$result) {
            $emails = $this->getRelatedEmailsProvider()->getEmails($object);
            $result = reset($emails);
        }

        return $result ?: '';
    }

    /**
     * Gets the recipients of the given thread
     *
     * @param EmailThread $thread
     * @return EmailRecipient[]
     * @deprecated since 2.3. Use EmailGridResultHelper::addEmailRecipients instead
     */
    public function getEmailThreadRecipients($thread)
    {
        /** @var EmailRecipientRepository $repo */
        $repo = $this->getRepository('OroEmailBundle:EmailRecipient');

        return $repo->getThreadUniqueRecipients($thread);
    }

    /**
     * Gets the attachments of the given thread
     *
     * @param EmailThread $thread
     * @return EmailAttachment[]
     */
    public function getEmailThreadAttachments($thread)
    {
        /** @var EmailAttachmentRepository $repo */
        $repo = $this->getRepository('OroEmailBundle:EmailAttachment');

        return $repo->getThreadAttachments($thread);
    }

    /**
     * Gets the email address name
     *
     * @param string $email
     * @return string The email address name or empty string if the name is not found
     */
    public function getEmailAddressName($email)
    {
        $result = $this->getEmailAddressHelper()->extractEmailAddressName($email);

        if (null === $result) {
            $result = '';
        }

        return $result;
    }

    /**
     * Gets the email address
     *
     * @param string $email
     * @return string The email address or empty string if the address is not found
     */
    public function getEmailAddress($email)
    {
        $result = $this->getEmailAddressHelper()->extractPureEmailAddress($email);

        if (null === $result) {
            $result = '';
        }

        return $result;
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
        $manager = $this->getEmailAttachmentManager();
        $targetEntityClass = ClassUtils::getRealClass($targetEntity);

        return
            0 === $manager->validateEmailAttachmentForTargetClass($emailAttachment, $targetEntityClass)->count()
            && !$manager->isAttached($emailAttachment, $targetEntity);
    }

    /**
     * Return unique identificator for websocket event. This identification
     * is used in notification widget to show message about new emails
     *
     * @return string
     */
    public function getEmailWSChannel()
    {
        $tokenAccessor = $this->getTokenAccessor();
        $currentUser = $tokenAccessor->getUser();
        if (null === $currentUser) {
            return '';
        }

        return WebSocketSendProcessor::getUserTopic(
            $currentUser,
            $tokenAccessor->getOrganization()
        );
    }

    /**
     * Return array of numbers unread emails per folder
     *
     * @return array
     */
    public function getUnreadEmailsCount()
    {
        $tokenAccessor = $this->getTokenAccessor();
        $currentUser = $tokenAccessor->getUser();
        if (null === $currentUser || !$this->getAuthorizationChecker()->isGranted('oro_email_email_user_view')) {
            return [];
        }

        $currentOrganization = $tokenAccessor->getOrganization();
        /** @var EmailRepository $repo */
        $repo = $this->getRepository('OroEmailBundle:Email');
        $result = $repo->getCountNewEmailsPerFolders($currentUser, $currentOrganization);
        $total = $repo->getCountNewEmails($currentUser, $currentOrganization);
        $result[] = ['num' => $total, 'id' => 0];

        return $result;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getMailboxProcessLabel($type)
    {
        return $this->getMailboxProcessStorage()->getProcess($type)->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

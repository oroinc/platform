<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderNameInterface;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EmailBundle\Provider\UrlProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with emails:
 *   - oro_get_email
 *   - oro_get_full_name_email
 *   - oro_get_email_address_name
 *   - oro_get_email_address
 *   - oro_get_email_thread_attachments
 *   - oro_can_attache
 *   - oro_get_mailbox_process_label
 *   - oro_get_email_ws_event
 *   - oro_get_unread_emails_count
 *   - oro_get_absolute_url
 */
class EmailExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

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
        return $this->container->get(ManagerRegistry::class);
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
        return $this->container->get(AuthorizationCheckerInterface::class);
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

    protected function getUrlProvider(): UrlProvider
    {
        return $this->container->get('oro_email.provider.url_provider');
    }

    protected function getAclHelper(): AclHelper
    {
        return $this->container->get(AclHelper::class);
    }

    /**
     * @param string $entityClass
     * @return EntityRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getDoctrine()->getManagerForClass($entityClass)->getRepository($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_get_email', [$this, 'getEmail']),
            new TwigFunction('oro_get_full_name_email', [$this, 'getFullNameEmail']),
            new TwigFunction('oro_get_email_address_name', [$this, 'getEmailAddressName']),
            new TwigFunction('oro_get_email_address', [$this, 'getEmailAddress']),
            new TwigFunction('oro_get_email_thread_attachments', [$this, 'getEmailThreadAttachments']),
            new TwigFunction('oro_can_attache', [$this, 'canReAttach']),
            new TwigFunction('oro_get_mailbox_process_label', [$this, 'getMailboxProcessLabel']),
            new TwigFunction('oro_get_email_ws_event', [$this, 'getEmailWSChannel']),
            new TwigFunction('oro_get_unread_emails_count', [$this, 'getUnreadEmailsCount']),
            new TwigFunction('oro_get_absolute_url', [$this, 'getAbsoluteUrl'])
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
     * Gets the email address with full name of the given object
     * If the name is not available, it defaults to $this->>getEmail()
     *
     * @param object $object
     * @return string Full name and email address
     * "Amanda Cole" <AmandaRCole@example.org>
     */
    public function getFullNameEmail($object)
    {
        if (!is_a($object, EmailHolderNameInterface::class)) {
            return $this->getEmail($object);
        }

        $email = $this->getEmail($object);

        /** @var EmailHolderNameInterface $object */
        $name = $object->getEmailHolderName();

        return $this->getEmailAddressHelper()->buildFullEmailAddress($email, $name);
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
        $total = $repo->getCountNewEmails($currentUser, $currentOrganization, null, $this->getAclHelper());
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
     * @param $route
     * @param array $routeParams
     * @return string
     */
    public function getAbsoluteUrl($route, $routeParams = []): string
    {
        return $this->getUrlProvider()->getAbsoluteUrl($route, $routeParams);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_email.email_holder_helper' => EmailHolderHelper::class,
            'oro_email.email.address.helper' => EmailAddressHelper::class,
            'oro_email.manager.email_attachment_manager' => EmailAttachmentManager::class,
            'oro_email.mailbox.process_storage' => MailboxProcessStorage::class,
            'oro_security.token_accessor' => TokenAccessorInterface::class,
            'oro_email.related_emails.provider' => RelatedEmailsProvider::class,
            'oro_email.provider.url_provider' => UrlProvider::class,
            ManagerRegistry::class,
            AuthorizationCheckerInterface::class,
            AclHelper::class,
        ];
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;

class EmailExtension extends \Twig_Extension
{
    const NAME = 'oro_email';

    /** @var EmailHolderHelper */
    protected $emailHolderHelper;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EmailHolderHelper $emailHolderHelper
     * @param EmailAddressHelper $emailAddressHelper
     * @param EntityManager $em
     */
    public function __construct(
        EmailHolderHelper $emailHolderHelper,
        EmailAddressHelper $emailAddressHelper,
        EntityManager $em)
    {
        $this->emailHolderHelper = $emailHolderHelper;
        $this->emailAddressHelper = $emailAddressHelper;
        $this->em = $em;
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
            new \Twig_SimpleFunction('oro_get_email_thread_attachments', [$this, 'getEmailThreadAttachments'])
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
        $result = $this->em->getRepository("OroEmailBundle:EmailRecipient")->getThreadRecipients($thread);

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
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

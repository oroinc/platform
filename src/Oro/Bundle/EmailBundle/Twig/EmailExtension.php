<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;

class EmailExtension extends \Twig_Extension
{
    const NAME = 'oro_email';

    /** @var EmailHolderHelper */
    protected $emailHolderHelper;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /**
     * @param EmailHolderHelper $emailHolderHelper
     */
    public function __construct(EmailHolderHelper $emailHolderHelper, EmailAddressHelper $emailAddressHelper)
    {
        $this->emailHolderHelper = $emailHolderHelper;
        $this->emailAddressHelper = $emailAddressHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_get_email', [$this, 'getEmail']),
            new \Twig_SimpleFunction('oro_get_email_address_name', [$this, 'getEmailAddressName']),
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
     * Gets the email address name
     *
     * @param object $object
     * @return string The email address name or empty string if the object has no email
     */
    public function getEmailAddressName($object)
    {
        $result = $this->emailAddressHelper->extractEmailAddressName($object);

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

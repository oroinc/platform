<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;

class EmailExtension extends \Twig_Extension
{
    const NAME = 'oro_email';

    /** @var EmailHolderHelper */
    protected $emailHolderHelper;

    /**
     * @param EmailHolderHelper $emailHolderHelper
     */
    public function __construct(EmailHolderHelper $emailHolderHelper)
    {
        $this->emailHolderHelper = $emailHolderHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_get_email', [$this, 'getEmail']),
            new \Twig_SimpleFunction('oro_is_attachment_in_context', [$this, 'isAttachmentInContext']),
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
     * Gets the email address of the given object
     *
     * @param object $object
     * @return bool
     */
    public function isAttachmentInContext($context, $file)
    {
        $result = $this->emailHolderHelper->getEmail($object);

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

<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EmailBundle\Entity\Util\EmailUtil;

class EmailExtension extends \Twig_Extension
{
    const NAME = 'oro_email';

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_has_email', [$this, 'hasEmail']),
            new \Twig_SimpleFunction('oro_get_email', [$this, 'getEmail']),
        ];
    }

    /**
     * Checks if the given object can have the email address
     *
     * @param object|string $objectOrClassName
     * @return bool
     */
    public function hasEmail($objectOrClassName)
    {
        return EmailUtil::hasEmail($objectOrClassName);
    }

    /**
     * Gets the email address of the given object
     *
     * @param object $object
     * @return string The email address or empty string if the object has no email
     */
    public function getEmail($object)
    {
        $result = EmailUtil::getEmail($object);

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

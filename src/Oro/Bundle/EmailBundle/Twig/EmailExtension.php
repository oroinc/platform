<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

class EmailExtension extends \Twig_Extension
{
    const NAME = 'oro_email';

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_is_email_holder', [$this, 'isEmailHolder']),
            new \Twig_SimpleFunction('oro_get_email', [$this, 'getEmail']),
        ];
    }

    /**
     * Checks if the given object implements EmailHolderInterface
     *
     * @param object $object
     * @return bool
     */
    public function isEmailHolder($object)
    {
        if (!is_object($object)) {
            return false;
        }

        return $object instanceof EmailHolderInterface;
    }

    /**
     * Gets the email address of the given object if it implements EmailHolderInterface
     *
     * @param object $object
     * @return string
     */
    public function getEmail($object)
    {
        $result = null;

        if ($this->isEmailHolder($object)) {
            $result = $object->getEmail();
        }

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

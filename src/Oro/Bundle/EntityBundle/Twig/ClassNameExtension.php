<?php

namespace Oro\Bundle\EntityBundle\Twig;

use Symfony\Component\Security\Core\Util\ClassUtils;

class ClassNameExtension extends \Twig_Extension
{
    const NAME = 'oro_class_name';

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_class_name', array($this, 'getClassName')),
        );
    }

    /**
     * Get FQCN of specified entity
     *
     * @param object $object
     * @return string
     */
    public function getClassName($object)
    {
        if (!is_object($object)) {
            return null;
        }

        return ClassUtils::getRealClass($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

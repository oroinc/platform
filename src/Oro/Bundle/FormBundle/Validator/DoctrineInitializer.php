<?php

namespace Oro\Bundle\FormBundle\Validator;

use Symfony\Bridge\Doctrine\Validator\DoctrineInitializer as BaseDoctrineInitializer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\OrderedHashMap;

class DoctrineInitializer extends BaseDoctrineInitializer
{
    /**
     * {@inheritdoc}
     *
     * The default Symfony's implementation is overridden to avoid initialization
     * of all entity managers for widely used types of objects
     * that are not Doctrine manageable entities.
     * @see \Symfony\Bridge\Doctrine\Validator\DoctrineInitializer::initialize
     */
    public function initialize($object)
    {
        if ($object instanceof FormInterface
            || $object instanceof OrderedHashMap
        ) {
            return;
        }

        parent::initialize($object);
    }
}

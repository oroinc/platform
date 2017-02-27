<?php

namespace Oro\Bundle\FormBundle\Validator;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\OrderedHashMap;
use Symfony\Component\Validator\ObjectInitializerInterface;

/**
 * The default Symfony's implementation is decorated to avoid initialization
 * of all entity managers for widely used types of objects
 * that are not Doctrine manageable entities.
 * @see \Symfony\Bridge\Doctrine\Validator\DoctrineInitializer::initialize
 */
class DoctrineInitializer implements ObjectInitializerInterface
{
    /** @var ObjectInitializerInterface */
    private $innerInitializer;

    /**
     * @param ObjectInitializerInterface $innerInitializer
     */
    public function __construct(ObjectInitializerInterface $innerInitializer)
    {
        $this->innerInitializer = $innerInitializer;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize($object)
    {
        if ($object instanceof FormInterface
            || $object instanceof OrderedHashMap
        ) {
            return;
        }

        $this->innerInitializer->initialize($object);
    }
}

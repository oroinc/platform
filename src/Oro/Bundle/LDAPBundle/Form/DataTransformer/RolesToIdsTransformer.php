<?php

namespace Oro\Bundle\LDAPBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\TranslationBundle\Form\DataTransformer\CollectionToArrayTransformer;

class RolesToIdsTransformer implements DataTransformerInterface
{
    /** @var EntitiesToIdsTransformer */
    protected $entityTransformer;

    /** @var CollectionToArrayTransformer */
    protected $collectionTransformer;

    /**
     * @param EntityManager $em
     * @param string $className
     */
    public function __construct(EntityManager $em, $className)
    {
        $this->entityTransformer = new EntitiesToIdsTransformer($em, $className);
        $this->collectionTransformer = new CollectionToArrayTransformer();
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            $value = [];
        }

        $entities = $this->entityTransformer->reverseTransform($value);

        return $this->collectionTransformer->reverseTransform($entities);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $this->entityTransformer->transform($value);
    }
}

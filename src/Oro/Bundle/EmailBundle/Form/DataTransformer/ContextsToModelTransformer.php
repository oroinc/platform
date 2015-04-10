<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;

class ContextsToModelTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        $targets = explode(';', $value);
        $result = [];
        foreach ($targets as $target) {
            $target = json_decode($target, true);
            $metadata = $this->entityManager->getClassMetadata($target['entityClass']);
            $result[] = $this->entityManager->getRepository($metadata->getName())->find($target['entityId']);
        }

        return $result;
    }
}

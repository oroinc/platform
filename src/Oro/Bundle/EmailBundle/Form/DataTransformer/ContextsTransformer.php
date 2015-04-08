<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Doctrine\ORM\EntityManager;

class ContextsTransformer implements DataTransformerInterface
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

        $data = implode(',', $value);
        $targets = explode(';', $data);
        $result = [];
        foreach ($targets as $target) {
            $target = json_decode($target, true);
            $metadata = $this->entityManager->getClassMetadata($target['entityClass']);
            $result[] = $this->entityManager->getRepository($metadata->getName())->find($target['entityId']);
        }

        return $result;
    }
}

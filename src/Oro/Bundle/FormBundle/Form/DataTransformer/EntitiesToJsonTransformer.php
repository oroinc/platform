<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;

class EntitiesToJsonTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return '';
        }

        if (is_array($value)) {
            foreach ($value as $target) {
                $result[] = json_encode([
                    'entityClass' => ClassUtils::getClass($target),
                    'entityId'    => $target->getId(),
                ]);
            }

            $value = implode(';', $result);
        }

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

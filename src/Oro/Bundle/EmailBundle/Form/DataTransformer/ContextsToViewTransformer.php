<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;

class ContextsToViewTransformer implements DataTransformerInterface
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
        return $value;
    }
}

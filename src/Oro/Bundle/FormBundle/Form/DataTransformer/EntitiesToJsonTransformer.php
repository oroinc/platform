<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms between a list of entities and its JSON representation.
 */
class EntitiesToJsonTransformer implements DataTransformerInterface
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function transform($value): mixed
    {
        if (!$value) {
            return '';
        }

        if (\is_array($value)) {
            $result = [];
            foreach ($value as $target) {
                $result[] = json_encode([
                    'entityClass' => ClassUtils::getClass($target),
                    'entityId' => $target->getId()
                ], JSON_THROW_ON_ERROR);
            }
            $value = implode(';', $result);
        }

        return $value;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return [];
        }

        $targets = explode(';', $value);
        $result = [];
        foreach ($targets as $target) {
            $target = json_decode($target, true, 512, JSON_THROW_ON_ERROR);
            $result[] = $this->doctrine->getRepository($target['entityClass'])->find($target['entityId']);
        }

        return $result;
    }
}

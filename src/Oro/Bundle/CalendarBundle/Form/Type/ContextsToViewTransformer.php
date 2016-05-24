<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer as BaseTransformer;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\UserBundle\Entity\User;

class ContextsToViewTransformer extends BaseTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return parent::transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $entities = parent::reverseTransform($value);
        if (!$entities) {
            return $entities;
        }

        return array_map(
            function ($entity) {
                if ($entity instanceof User) {
                    return (new Attendee())
                        ->setDisplayName($entity->getFullName())
                        ->setEmail($entity->getEmail())
                        ->setUser($entity);
                }

                return $entity;
            },
            $entities
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassLabel($className)
    {
        return null;
    }
}

<?php

namespace Oro\Bundle\ActivityListBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

class PlaceholderFilter
{
    /**
     * Checks if the entity can has notes
     *
     * @param object $entity
     * @return bool
     */
    public function isApplicable($entity)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $className = ClassUtils::getClass($entity);

        /**
         * TODO:
         *  Do entity can have some activities assigned ??
         *  validation should be done by chain provider
         */
        return in_array(
            $className,
            [
                'OroCRM\Bundle\ContactBundle\Entity\Contact',
            ]
        );
    }
}

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
         *  validation should be checked by interface class or by provider
         */
        return in_array(
            $className,
            [
                'OroCRM\Bundle\ContactBundle\Entity\Contact',
            ]
        );
    }
}

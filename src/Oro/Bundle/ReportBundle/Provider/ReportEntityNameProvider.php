<?php

namespace Oro\Bundle\ReportBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

/**
 * Report entity title provider
 * Will return report's 'name' or 'id' as fallback
 */
class ReportEntityNameProvider implements EntityNameProviderInterface
{
    const CLASS_NAME = 'Oro\Bundle\ReportBundle\Entity\Report';

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!in_array($format, [self::SHORT, self::FULL]) || !is_a($entity, static::CLASS_NAME)) {
            return false;
        }

        return $entity->getName() ?: $entity->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!in_array($format, [self::SHORT, self::FULL]) || $className !== self::CLASS_NAME) {
            return false;
        }

        return sprintf('COALESCE(NULLIF(%1$s.name, \'\'), %1$s.id)', $alias);
    }
}

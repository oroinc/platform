<?php

namespace Oro\Bundle\ReportBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * Provides a text representation of Report entity.
 */
class ReportEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Report || !\in_array($format, [self::SHORT, self::FULL], true)) {
            return false;
        }

        return $entity->getName() ?: $entity->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Report::class, true) || !\in_array($format, [self::SHORT, self::FULL], true)) {
            return false;
        }

        return sprintf('COALESCE(NULLIF(%1$s.name, \'\'), %1$s.id)', $alias);
    }
}

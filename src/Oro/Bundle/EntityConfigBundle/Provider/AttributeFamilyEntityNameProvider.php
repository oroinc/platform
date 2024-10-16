<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Provides a text representation of AttributeFamily entity.
 */
class AttributeFamilyEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof AttributeFamily || self::FULL !== $format) {
            return false;
        }

        $localizedLabel = $locale instanceof Localization
            ? (string)$entity->getLabel($locale)
            : null;

        return $localizedLabel ?: (string)$entity->getDefaultLabel() ?: $entity->getCode();
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, AttributeFamily::class, true) || self::FULL !== $format) {
            return false;
        }

        if ($locale instanceof Localization) {
            return sprintf(
                'CAST((SELECT COALESCE(%1$s_l.string, %1$s_l.text,'
                . ' NULLIF(COALESCE(%1$s_dl.string, %1$s_dl.text), \'\'), %1$s.code) FROM %2$s %1$s_dl'
                . ' LEFT JOIN %2$s %1$s_l WITH %1$s_l MEMBER OF %1$s.labels AND %1$s_l.localization = %3$s'
                . ' WHERE %1$s_dl MEMBER OF %1$s.labels AND %1$s_dl.localization IS NULL) AS string)',
                $alias,
                LocalizedFallbackValue::class,
                $locale->getId()
            );
        }

        return sprintf(
            'CAST((SELECT COALESCE(NULLIF(COALESCE(%1$s_l.string, %1$s_l.text), \'\'), %1$s.code) FROM %2$s %1$s_l'
            . ' WHERE %1$s_l MEMBER OF %1$s.labels AND %1$s_l.localization IS NULL) AS string)',
            $alias,
            LocalizedFallbackValue::class
        );
    }
}

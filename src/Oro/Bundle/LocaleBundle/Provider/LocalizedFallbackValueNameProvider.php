<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class will use localization name for localized entity name.
 */
class LocalizedFallbackValueNameProvider implements EntityNameProviderInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL
            && (is_a($entity, LocalizedFallbackValue::class) || is_a($entity, AbstractLocalizedFallbackValue::class))
        ) {
            return $entity->getLocalization()?->getName()
                ?? $this->translator->trans('oro.locale.fallback.value.default');
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        return false;
    }
}

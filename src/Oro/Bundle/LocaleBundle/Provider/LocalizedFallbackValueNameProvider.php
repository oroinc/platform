<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a text representation of AbstractLocalizedFallbackValue entities.
 */
class LocalizedFallbackValueNameProvider implements EntityNameProviderInterface
{
    private const TRANSLATION_KEY = 'oro.locale.fallback.value.default';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if ($entity instanceof AbstractLocalizedFallbackValue) {
            $localization = $entity->getLocalization();

            return null !== $localization
                ? $localization->getName()
                : $this->trans(self::TRANSLATION_KEY, $locale);
        }

        return false;
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (is_a($className, AbstractLocalizedFallbackValue::class, true)) {
            return sprintf(
                '(SELECT COALESCE(%1$s_loc.name, %3$s) FROM %2$s %1$s_lfv'
                . ' LEFT JOIN %1$s_lfv.localization %1$s_loc WITH %1$s_loc = %1$s.localization'
                . ' WHERE %1$s_lfv = %1$s)',
                $alias,
                $className,
                (string)(new Expr())->literal($this->trans(self::TRANSLATION_KEY, $locale))
            );
        }

        return false;
    }

    private function trans(string $key, string|Localization|null $locale): string
    {
        if ($locale instanceof Localization) {
            $locale = $locale->getLanguageCode();
        }

        return $this->translator->trans($key, [], null, $locale);
    }
}

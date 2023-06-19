<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\MiddleNameInterface;
use Oro\Bundle\LocaleBundle\Model\NamePrefixInterface;
use Oro\Bundle\LocaleBundle\Model\NameSuffixInterface;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provides a text representation of entities that implement "name part" interface(s).
 */
class EntityNameProvider implements EntityNameProviderInterface
{
    /** @var ServiceLink */
    protected $nameFormatterLink;

    /** @var ServiceLink */
    protected $dqlNameFormatterLink;

    public function __construct(ServiceLink $nameFormatterLink, ServiceLink $dqlNameFormatterLink)
    {
        $this->nameFormatterLink = $nameFormatterLink;
        $this->dqlNameFormatterLink = $dqlNameFormatterLink;
    }

    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (self::FULL !== $format || !$this->isFullFormatSupported(\get_class($entity))) {
            return false;
        }

        /** @var NameFormatter $nameFormatter */
        $nameFormatter = $this->nameFormatterLink->getService();

        return $nameFormatter->format(
            $entity,
            $locale instanceof Localization ? $locale->getLanguageCode() : $locale
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (self::FULL !== $format || !$this->isFullFormatSupported($className)) {
            return false;
        }

        /** @var DQLNameFormatter $dqlNameFormatter */
        $dqlNameFormatter = $this->dqlNameFormatterLink->getService();

        return $dqlNameFormatter->getFormattedNameDQL(
            $alias,
            $className,
            $locale instanceof Localization ? $locale->getLanguageCode() : $locale
        );
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isFullFormatSupported($className)
    {
        if (is_a($className, FirstNameInterface::class, true)) {
            return true;
        }
        if (is_a($className, LastNameInterface::class, true)) {
            return true;
        }
        if (is_a($className, MiddleNameInterface::class, true)) {
            return true;
        }
        if (is_a($className, NamePrefixInterface::class, true)) {
            return true;
        }
        if (is_a($className, NameSuffixInterface::class, true)) {
            return true;
        }

        return false;
    }
}

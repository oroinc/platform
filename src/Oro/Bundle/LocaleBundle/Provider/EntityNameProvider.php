<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

class EntityNameProvider implements EntityNameProviderInterface
{
    /** @var ServiceLink */
    protected $nameFormatterLink;

    /** @var ServiceLink */
    protected $dqlNameFormatterLink;

    /**
     * @param ServiceLink $nameFormatterLink
     * @param ServiceLink $dqlNameFormatterLink
     */
    public function __construct(ServiceLink $nameFormatterLink, ServiceLink $dqlNameFormatterLink)
    {
        $this->nameFormatterLink    = $nameFormatterLink;
        $this->dqlNameFormatterLink = $dqlNameFormatterLink;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === self::FULL && $this->isFullFormatSupported(get_class($entity))) {
            /** @var NameFormatter $nameFormatter */
            $nameFormatter = $this->nameFormatterLink->getService();

            return $nameFormatter->format($entity, $locale);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === self::FULL && $this->isFullFormatSupported($className)) {
            /** @var DQLNameFormatter $dqlNameFormatter */
            $dqlNameFormatter = $this->dqlNameFormatterLink->getService();

            return $dqlNameFormatter->getFormattedNameDQL($alias, $className, $locale);
        }

        return false;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isFullFormatSupported($className)
    {
        if (is_a($className, 'Oro\Bundle\LocaleBundle\Model\FirstNameInterface', true)) {
            return true;
        }
        if (is_a($className, 'Oro\Bundle\LocaleBundle\Model\LastNameInterface', true)) {
            return true;
        }
        if (is_a($className, 'Oro\Bundle\LocaleBundle\Model\MiddleNameInterface', true)) {
            return true;
        }
        if (is_a($className, 'Oro\Bundle\LocaleBundle\Model\NamePrefixInterface', true)) {
            return true;
        }
        if (is_a($className, 'Oro\Bundle\LocaleBundle\Model\NameSuffixInterface', true)) {
            return true;
        }

        return false;
    }
}

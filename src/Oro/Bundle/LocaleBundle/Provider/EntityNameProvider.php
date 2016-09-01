<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Component\DependencyInjection\ServiceLink;
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
        if ($format !== self::FULL || !$this->isFullFormatSupported($className)) {
            return false;
        }

        /** @var DQLNameFormatter $dqlNameFormatter */
        $dqlNameFormatter = $this->dqlNameFormatterLink->getService();

        $nameDQL = $dqlNameFormatter->getFormattedNameDQL($alias, $className, $locale);

        $subSelects = [];

        foreach ($this->getSubSelectsMap() as $class => $template) {
            if (is_a($className, $class, true)) {
                $subSelects[] = 'CAST((' . sprintf($template, $alias, $className) . ') AS string)';
            }
        }

        if (count($subSelects) > 0) {
            $nameDQL = sprintf('COALESCE(NULLIF(%s, \'\'), %s)', $nameDQL, join(', ', $subSelects));
        }

        // Need to forcibly convert expression to string when the title is different type.
        // Example of error: "UNION types text and integer cannot be matched".
        return 'CAST(' . $nameDQL .' as string)';
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

    /**
     * Returns a map of classes and getNameDQL subQuery templates
     * SubQueries will be added as fallback for entities that implement/extend any of the listed classes
     * %1$s is the entity alias, %2$s is className of the entity
     *
     * @return array
     */
    protected function getSubSelectsMap()
    {
        return [
            'Oro\Bundle\AddressBundle\Entity\EmailCollectionInterface' =>
                'SELECT %1$s_emails.email FROM %2$s %1$s_emails_base' .
                ' LEFT JOIN %1$s_emails_base.emails %1$s_emails' .
                ' WHERE %1$s_emails.primary = true AND %1$s_emails_base = %1$s',
            'Oro\Bundle\AddressBundle\Entity\PhoneCollectionInterface' =>
                'SELECT %1$s_phones.phone FROM %2$s %1$s_phones_base' .
                ' LEFT JOIN %1$s_phones_base.phones %1$s_phones' .
                ' WHERE %1$s_phones.primary = true AND %1$s_phones_base = %1$s',
        ];
    }
}

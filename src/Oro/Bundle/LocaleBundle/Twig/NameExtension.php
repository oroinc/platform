<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\EntityBundle\Twig\EntityExtension;
use Oro\Bundle\LocaleBundle\Model\NamePrefixInterface;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\MiddleNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\NameSuffixInterface;

/**
 * @deprecated since 1.8, use Oro\Bundle\EntityBundle\Twig\EntityExtension
 */
class NameExtension
{
    /**
     * @var EntityExtension
     */
    protected $entityExtension;

    /**
     * @param EntityExtension $entityExtension
     */
    public function __construct(EntityExtension $entityExtension)
    {
        $this->entityExtension = $entityExtension;
    }

    /**
     * Formats person name according to locale settings.
     *
     * @param NamePrefixInterface|FirstNameInterface|MiddleNameInterface|LastNameInterface|NameSuffixInterface $person
     * @param string $locale
     * @return string
     *
     * @deprecated since 1.8, use Oro\Bundle\EntityBundle\Twig\EntityExtension::getEntityName
     */
    public function format($person, $locale = null)
    {
        return $this->entityExtension->getEntityName($person, $locale);
    }
}

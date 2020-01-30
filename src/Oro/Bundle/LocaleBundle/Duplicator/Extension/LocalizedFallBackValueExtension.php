<?php

namespace Oro\Bundle\LocaleBundle\Duplicator\Extension;

use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\DraftBundle\Duplicator\Extension\AbstractDuplicatorExtension;
use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Responsible for copying behavior of LocalizedFallbackValue type parameter.
 */
class LocalizedFallBackValueExtension extends AbstractDuplicatorExtension
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return new DoctrineCollectionFilter();
    }

    /**
     * @return Matcher
     */
    public function getMatcher(): Matcher
    {
        $source = $this->getContext()->offsetGet('source');
        $properties = $this->getLocalizedFallbackValueProperties($source);

        return new PropertiesNameMatcher($properties);
    }

    /**
     * @param DraftableInterface $source
     *
     * @return bool
     */
    public function isSupport(DraftableInterface $source): bool
    {
        $properties = $this->getLocalizedFallbackValueProperties($source);

        return !empty($properties);
    }


    /**
     * @param DraftableInterface $source
     *
     * @return array
     */
    private function getLocalizedFallbackValueProperties(DraftableInterface $source): array
    {
        $properties = [];
        $em = $this->managerRegistry->getManager();
        /** @var ClassMetadataInfo $metadata */
        $metadata = $em->getClassMetadata(ClassUtils::getRealClass($source));
        foreach ($metadata->getAssociationMappings() as $name => $fieldMapping) {
            if ($fieldMapping['targetEntity'] === LocalizedFallbackValue::class) {
                $properties[] = $name;
            }
        }

        return $properties;
    }
}

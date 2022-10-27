<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Extension;

use DeepCopy\Filter\Filter;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\Matcher;
use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Manager\DraftManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Responsible for modifying ownership properties.
 */
class OwnershipExtension extends AbstractDuplicatorExtension
{
    /**
     * @var OwnershipMetadataProviderInterface
     */
    private $ownershipMetadataProvider;

    public function __construct(OwnershipMetadataProviderInterface $ownershipMetadataProvider)
    {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    public function getFilter(): Filter
    {
        return new SetNullFilter();
    }

    public function getMatcher(): Matcher
    {
        $ownerProperties = $this->getSourceOwnerShipProperties();

        return new PropertiesNameMatcher($ownerProperties);
    }

    public function isSupport(DraftableInterface $source): bool
    {
        $className = ClassUtils::getRealClass($source);

        return $this->getContext()->offsetGet('action') === DraftManager::ACTION_CREATE_DRAFT
            && $this->ownershipMetadataProvider->getMetadata($className)->hasOwner();
    }

    /**
     * @return string[]
     */
    private function getSourceOwnerShipProperties(): array
    {
        $source = $this->getSource();
        $metadata = $this->ownershipMetadataProvider->getMetadata(ClassUtils::getRealClass($source));
        $ownerProperties = [$metadata->getOwnerFieldName(), $metadata->getOrganizationFieldName()];

        return array_unique(array_filter($ownerProperties));
    }

    private function getSource(): DraftableInterface
    {
        return $this->getContext()->offsetGet('source');
    }
}

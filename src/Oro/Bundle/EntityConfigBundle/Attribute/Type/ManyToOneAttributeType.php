<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides metadata about many-to-one association attribute type.
 */
class ManyToOneAttributeType implements AttributeTypeInterface
{
    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(EntityNameResolver $entityNameResolver, DoctrineHelper $doctrineHelper)
    {
        $this->entityNameResolver = $entityNameResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function isSearchable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function isFilterable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function isSortable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->entityNameResolver->getName($originalValue, null, $localization);
    }

    #[\Override]
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return is_object($originalValue)
            ? $this->doctrineHelper->getSingleEntityIdentifier($originalValue, false)
            : null;
    }

    #[\Override]
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->getSearchableValue($attribute, $originalValue, $localization);
    }
}

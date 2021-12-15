<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use InvalidArgumentException;
use Laminas\Stdlib\Guard\ArrayOrTraversableGuardTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use RuntimeException;

/**
 * Provides metadata about many-to-many association attribute type.
 */
class ManyToManyAttributeType implements AttributeTypeInterface
{
    use AttributeLocalizableTrait;
    use ArrayOrTraversableGuardTrait;
    use FallbackTrait;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    public function __construct(EntityNameResolver $entityNameResolver, DoctrineHelper $doctrineHelper)
    {
        $this->entityNameResolver = $entityNameResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable(FieldConfigModel $attribute)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(FieldConfigModel $attribute)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isSortable(FieldConfigModel $attribute)
    {
        return $this->isAttributeLocalizable($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->getFilterableValue($attribute, $originalValue, $localization);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        if ($this->isAttributeLocalizable($attribute)) {
            return (string)$this->getLocalizedFallbackValue($originalValue, $localization);
        }

        $this->ensureTraversable($originalValue);

        $values = [];
        foreach ($originalValue as $entity) {
            $values[] = $this->entityNameResolver->getName($entity, null, $localization);
        }

        return implode(' ', $values);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        if ($this->isAttributeLocalizable($attribute)) {
            return (string)$this->getLocalizedFallbackValue($originalValue, $localization);
        }

        throw new RuntimeException('Not supported');
    }

    /**
     * @param string $originalValue
     * @throws InvalidArgumentException
     */
    protected function ensureTraversable($originalValue)
    {
        $this->guardForArrayOrTraversable($originalValue, 'Value', InvalidArgumentException::class);
    }
}

<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Zend\Stdlib\Guard\ArrayOrTraversableGuardTrait;

class ManyToManyAttributeType implements AttributeTypeInterface
{
    use AttributeLocalizableTrait;
    use ArrayOrTraversableGuardTrait;
    use FallbackTrait;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /**
     * @param EntityNameResolver $entityNameResolver
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(EntityNameResolver $entityNameResolver, DoctrineHelper $doctrineHelper)
    {
        $this->entityNameResolver = $entityNameResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'manyToMany';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable(FieldConfigModel $attribute = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(FieldConfigModel $attribute = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isSortable(FieldConfigModel $attribute = null)
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

        throw new \RuntimeException('Not supported');
    }

    /**
     * @param string $originalValue
     * @throws \InvalidArgumentException
     */
    protected function ensureTraversable($originalValue)
    {
        $this->guardForArrayOrTraversable($originalValue, 'Value', \InvalidArgumentException::class);
    }
}

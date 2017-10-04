<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Zend\Stdlib\Guard\ArrayOrTraversableGuardTrait;

class OneToManyAttributeType implements AttributeTypeInterface
{
    use ArrayOrTraversableGuardTrait;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /**
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(EntityNameResolver $entityNameResolver)
    {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'oneToMany';
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
        return false;
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

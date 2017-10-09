<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

trait AttributeLocalizableTrait
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param FieldConfigModel|null $attribute
     *
     * @return bool
     */
    protected function isAttributeLocalizable(FieldConfigModel $attribute = null)
    {
        if (!$attribute) {
            return false;
        }

        $config = $attribute->toArray('extend');
        if (isset($config['target_entity'])) {
            return is_a($config['target_entity'], LocalizedFallbackValue::class, true);
        }

        $fieldName = $attribute->getFieldName();
        $metadata = $this->doctrineHelper->getEntityMetadata($attribute->getEntity()->getClassName());
        if (!$metadata || !$metadata->hasAssociation($fieldName)) {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($fieldName);
        if (isset($mapping['targetEntity'])) {
            return is_a($mapping['targetEntity'], LocalizedFallbackValue::class, true);
        }

        return false;
    }
}

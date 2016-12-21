<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Component\EntitySerializer\DataNormalizer as BaseDataNormalizer;
use Oro\Component\EntitySerializer\ConfigUtil;
use Oro\Component\EntitySerializer\FieldConfig;

class DataNormalizer extends BaseDataNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getPropertyPath($field, FieldConfig $fieldConfig)
    {
        $propertyPath = parent::getPropertyPath($field, $fieldConfig);

        $targetConfig = $fieldConfig->getTargetEntity();
        if (null !== $targetConfig && $fieldConfig->isCollapsed()) {
            $childFields = $targetConfig->getFields();
            if (1 === count($childFields)) {
                reset($childFields);
                /** @var FieldConfig $childFieldConfig */
                list($childField, $childFieldConfig) = each($childFields);
                $propertyPath =
                    $fieldConfig->getPropertyPath($field)
                    . ConfigUtil::PATH_DELIMITER
                    . $childFieldConfig->getPropertyPath($childField);
            }
        }

        return $propertyPath;
    }
}

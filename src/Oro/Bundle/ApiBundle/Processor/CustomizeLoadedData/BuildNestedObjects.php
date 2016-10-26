<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Computes values of fields that represent nested objects.
 */
class BuildNestedObjects implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        $config = $context->getConfig();
        $hasChanges = false;
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->isExcluded() && DataType::isNestedObject($field->getDataType())) {
                $data[$fieldName] = $this->buildNestedObject($data, $field->getTargetEntity());
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $context->setResult($data);
        }
    }

    /**
     * @param array                  $data
     * @param EntityDefinitionConfig $config
     *
     * @return array|null
     */
    protected function buildNestedObject(array $data, EntityDefinitionConfig $config)
    {
        $result = [];
        $isEmpty = true;
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $value = $this->getValue($data, $field->getPropertyPath($fieldName));
            if (null !== $value) {
                $isEmpty = false;
            }
            $result[$fieldName] = $value;
        }

        return $isEmpty ? null : $result;
    }

    /**
     * @param array  $data
     * @param string $propertyPath
     *
     * @return mixed
     */
    protected function getValue(array $data, $propertyPath)
    {
        if (false !== strpos($propertyPath, '.')) {
            throw new RuntimeException(
                sprintf(
                    'The "%s" property path is not supported for the nested object.',
                    $propertyPath
                )
            );
        }

        $result = null;
        if (array_key_exists($propertyPath, $data)) {
            $result = $data[$propertyPath];
        }

        return $result;
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Computes a value of "primary" field based on a "primary" flag in a collection.
 */
class ComputePrimaryField implements ProcessorInterface
{
    /** @var string */
    protected $primaryFieldName;

    /** @var string */
    protected $associationName;

    /** @var string */
    protected $associationDataFieldName;

    /** @var string */
    protected $associationPrimaryFlagFieldName;

    /**
     * @param string $primaryFieldName
     * @param string $associationName
     * @param string $associationDataFieldName
     * @param string $associationPrimaryFlagFieldName
     */
    public function __construct(
        $primaryFieldName,
        $associationName,
        $associationDataFieldName,
        $associationPrimaryFlagFieldName = 'primary'
    ) {
        $this->primaryFieldName = $primaryFieldName;
        $this->associationName = $associationName;
        $this->associationDataFieldName = $associationDataFieldName;
        $this->associationPrimaryFlagFieldName = $associationPrimaryFlagFieldName;
    }

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
        $primaryField = $config->getField($this->primaryFieldName);
        if (!$primaryField || $primaryField->isExcluded()) {
            // undefined or excluded primary field
            return;
        }
        if (array_key_exists($this->primaryFieldName, $data)) {
            // the primary field is already set
            return;
        }

        $data[$this->primaryFieldName] = $this->getPrimaryValue($config, $data);
        $context->setResult($data);
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param array                  $data
     *
     * @return mixed
     */
    protected function getPrimaryValue(EntityDefinitionConfig $config, array $data)
    {
        $result = null;
        $association = $config->getField($this->associationName);
        if (null !== $association) {
            $associationName = $association->getPropertyPath() ?: $this->associationName;
            if (!empty($data[$associationName]) && is_array($data[$associationName])) {
                $associationTargetConfig = $association->getTargetEntity();
                if (null !== $associationTargetConfig) {
                    $result = $this->extractPrimaryValue(
                        $data[$associationName],
                        $this->getPropertyPath($associationTargetConfig, $this->associationDataFieldName),
                        $this->getPropertyPath($associationTargetConfig, $this->associationPrimaryFlagFieldName)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param array  $items
     * @param string $dataFieldName
     * @param string $primaryFlagFieldName
     *
     * @return mixed
     */
    protected function extractPrimaryValue(array $items, $dataFieldName, $primaryFlagFieldName)
    {
        $result = null;
        foreach ($items as $item) {
            if (is_array($item)
                && array_key_exists($primaryFlagFieldName, $item)
                && $item[$primaryFlagFieldName]
                && array_key_exists($dataFieldName, $item)
            ) {
                $result = $item[$dataFieldName];
                break;
            }
        }

        return $result;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string                 $fieldName
     *
     * @return string
     */
    protected function getPropertyPath(EntityDefinitionConfig $config, $fieldName)
    {
        $field = $config->getField($fieldName);
        if (null === $field) {
            return $fieldName;
        }

        return $field->getPropertyPath() ?: $fieldName;
    }
}

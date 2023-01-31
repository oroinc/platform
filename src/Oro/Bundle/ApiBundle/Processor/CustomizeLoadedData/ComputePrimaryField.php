<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "primary" field based on a "primary" flag in a collection.
 * For example this processor can be used to compute a value of a primary email based on
 * a collection of emails where each element of this collection has a "primary" boolean
 * property indicates whether an email is a primary one or not.
 * @see \Oro\Bundle\ApiBundle\Filter\PrimaryFieldFilter
 * @see \Oro\Bundle\ApiBundle\Processor\CustomizeFormData\MapPrimaryField
 */
class ComputePrimaryField implements ProcessorInterface
{
    private string $primaryFieldName;
    private string $associationName;
    private string $associationDataFieldName;
    private string $associationPrimaryFlagFieldName;

    public function __construct(
        string $primaryFieldName,
        string $associationName,
        string $associationDataFieldName,
        string $associationPrimaryFlagFieldName = 'primary'
    ) {
        $this->primaryFieldName = $primaryFieldName;
        $this->associationName = $associationName;
        $this->associationDataFieldName = $associationDataFieldName;
        $this->associationPrimaryFlagFieldName = $associationPrimaryFlagFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $primaryFieldName = $context->getResultFieldName($this->primaryFieldName);
        if ($context->isFieldRequested($primaryFieldName, $data)) {
            $data[$primaryFieldName] = $this->getPrimaryValue($context->getConfig(), $data);
            $context->setData($data);
        }
    }

    private function getPrimaryValue(EntityDefinitionConfig $config, array $data): mixed
    {
        $result = null;
        $associationName = $config->findFieldNameByPropertyPath($this->associationName);
        if ($associationName && !empty($data[$associationName]) && \is_array($data[$associationName])) {
            $associationTargetConfig = $config->getField($associationName)->getTargetEntity();
            if (null !== $associationTargetConfig) {
                $result = $this->extractPrimaryValue(
                    $data[$associationName],
                    $associationTargetConfig->findFieldNameByPropertyPath($this->associationDataFieldName),
                    $associationTargetConfig->findFieldNameByPropertyPath($this->associationPrimaryFlagFieldName)
                );
            }
        }

        return $result;
    }

    private function extractPrimaryValue(array $items, string $dataFieldName, string $primaryFlagFieldName): mixed
    {
        $result = null;
        foreach ($items as $item) {
            if (\is_array($item)
                && \array_key_exists($primaryFlagFieldName, $item)
                && $item[$primaryFlagFieldName]
                && \array_key_exists($dataFieldName, $item)
            ) {
                $result = $item[$dataFieldName];
                break;
            }
        }

        return $result;
    }
}

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

        $primaryFieldName = $context->getResultFieldName($this->primaryFieldName);
        if ($context->isFieldRequested($primaryFieldName, $data)) {
            $data[$primaryFieldName] = $this->getPrimaryValue($context->getConfig(), $data);
            $context->setResult($data);
        }
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
        $associationName = $config->findFieldNameByPropertyPath($this->associationName);
        if ($associationName) {
            $associationName = $config->findFieldNameByPropertyPath($this->associationName);
            if (!empty($data[$associationName]) && is_array($data[$associationName])) {
                $associationTargetConfig = $config->getField($associationName)->getTargetEntity();
                if (null !== $associationTargetConfig) {
                    $result = $this->extractPrimaryValue(
                        $data[$associationName],
                        $associationTargetConfig->findFieldNameByPropertyPath($this->associationDataFieldName),
                        $associationTargetConfig->findFieldNameByPropertyPath($this->associationPrimaryFlagFieldName)
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
}

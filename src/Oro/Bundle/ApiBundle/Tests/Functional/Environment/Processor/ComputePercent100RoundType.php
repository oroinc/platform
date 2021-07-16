<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ComputePercent100RoundType implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $context->setData($this->processCustomTypes($data, $config, $context->getClassName()));
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processCustomTypes(array $data, EntityDefinitionConfig $config, string $entityClass): array
    {
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            $dataType = $field->getDataType();
            if (!$dataType) {
                continue;
            }

            if (\array_key_exists($fieldName, $data)
                && 'test_percent_100_round' === $dataType
                && null !== $data[$fieldName]
            ) {
                $data[$fieldName] /= 100.0;
            }
        }

        return $data;
    }
}

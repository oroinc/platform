<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\AsyncOperation;

use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "source" field for an error occurred when processing an asynchronous operation.
 */
class ComputeErrorSource implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $errorSourceFieldName = $context->getResultFieldName('source');
        if (!$context->isFieldRequested($errorSourceFieldName)) {
            return;
        }

        $errorSource = $data[$errorSourceFieldName];
        if ($errorSource instanceof ErrorSource) {
            $data[$errorSourceFieldName] = $this->getSourceValue($data[$errorSourceFieldName]);
            $context->setData($data);
        }
    }

    private function getSourceValue(ErrorSource $errorSource): array
    {
        $result = [];
        if ($errorSource->getPropertyPath() !== null) {
            $result['propertyPath'] = $errorSource->getPropertyPath();
        }
        if ($errorSource->getPointer() !== null) {
            $result['pointer'] = $errorSource->getPointer();
        }
        if ($errorSource->getParameter() !== null) {
            $result['parameter'] = $errorSource->getParameter();
        }

        return $result;
    }
}

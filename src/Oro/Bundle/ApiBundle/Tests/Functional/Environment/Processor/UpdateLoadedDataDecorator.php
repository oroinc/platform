<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * This processor is used to test that both regular (exist in DIC)
 * and simple (without arguments and removed from DIC) processors can be decorated.
 */
class UpdateLoadedDataDecorator implements ProcessorInterface
{
    private ProcessorInterface $innerProcessor;

    public function __construct(ProcessorInterface $innerProcessor)
    {
        $this->innerProcessor = $innerProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $this->innerProcessor->process($context);

        $data = $context->getData();
        if (isset($data['computedName'])) {
            $data['computedName'] .= ' (decorated)';
            $context->setData($data);
        }
    }
}

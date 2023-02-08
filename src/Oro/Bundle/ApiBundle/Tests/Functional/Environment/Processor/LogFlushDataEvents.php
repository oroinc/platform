<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

class LogFlushDataEvents implements ProcessorInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $this->logger->info(
            sprintf(
                'Process "%s" event of "%s" action for "%s".',
                $context->getEvent(),
                $context->getAction(),
                $context->getClassName()
            ),
            [
                'requestType'   => (string)$context->getRequestType(),
                'version'       => $context->getVersion(),
                'parentAction'  => $context->getParentAction(),
                'propertyPath'  => $context->getPropertyPath(),
                'rootClassName' => $context->getRootClassName()
            ]
        );
    }
}

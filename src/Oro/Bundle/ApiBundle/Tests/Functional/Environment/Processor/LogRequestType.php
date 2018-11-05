<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

class LogRequestType implements ProcessorInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $this->logger->info(
            sprintf(
                'Process "%s" action for "%s" (%s)',
                $context->getAction(),
                $context->getClassName(),
                $context->isMasterRequest() ? 'MASTER_REQUEST' : 'SUB_REQUEST'
            ),
            [
                'requestType' => (string)$context->getRequestType(),
                'version'     => $context->getVersion()
            ]
        );
    }
}

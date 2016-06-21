<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;

class TraceableProcessorApplicableCheckerFactory implements ProcessorApplicableCheckerFactoryInterface
{
    /** @var ProcessorApplicableCheckerFactoryInterface */
    protected $applicableCheckerFactory;

    /** @var TraceLogger */
    protected $logger;

    /**
     * @param ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory
     * @param TraceLogger                                $logger
     */
    public function __construct(
        ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory,
        TraceLogger $logger
    ) {
        $this->applicableCheckerFactory = $applicableCheckerFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function createApplicableChecker()
    {
        $traceableChecker = new TraceableChainApplicableChecker($this->logger);
        $innerChecker = $this->applicableCheckerFactory->createApplicableChecker();
        foreach ($innerChecker as $checker) {
            $traceableChecker->addChecker($checker);
        }

        return $traceableChecker;
    }
}

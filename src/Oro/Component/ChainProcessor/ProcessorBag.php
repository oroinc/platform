<?php

namespace Oro\Component\ChainProcessor;

/**
 * The default implementation of ProcessorBagInterface.
 */
class ProcessorBag implements ProcessorBagInterface
{
    /** @var ProcessorBagConfigProviderInterface */
    protected $configProvider;

    /** @var ProcessorRegistryInterface */
    protected $processorRegistry;

    /** @var ProcessorIteratorFactoryInterface */
    protected $processorIteratorFactory;

    /** @var ProcessorApplicableCheckerFactoryInterface */
    protected $applicableCheckerFactory;

    /** @var bool */
    protected $debug;

    /** @var array */
    protected $additionalApplicableCheckers = [];

    /** @var ChainApplicableChecker */
    protected $processorApplicableChecker;

    public function __construct(
        ProcessorBagConfigProviderInterface $configProvider,
        ProcessorRegistryInterface $processorRegistry,
        bool $debug = false,
        ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory = null,
        ProcessorIteratorFactoryInterface $processorIteratorFactory = null
    ) {
        $this->configProvider = $configProvider;
        $this->processorRegistry = $processorRegistry;
        $this->debug = $debug;
        $this->applicableCheckerFactory = $applicableCheckerFactory ?: new ProcessorApplicableCheckerFactory();
        $this->processorIteratorFactory = $processorIteratorFactory ?: new ProcessorIteratorFactory();
        if ($this->processorIteratorFactory instanceof ProcessorBagAwareIteratorFactoryInterface) {
            $this->processorIteratorFactory->setProcessorBag($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addApplicableChecker(ApplicableCheckerInterface $checker, int $priority = 0): void
    {
        $this->additionalApplicableCheckers[$priority][] = $checker;
        $this->processorApplicableChecker = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors(ContextInterface $context): ProcessorIterator
    {
        $this->ensureProcessorApplicableCheckerInitialized();

        return $this->createProcessorIterator($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getActions(): array
    {
        return $this->configProvider->getActions();
    }

    /**
     * {@inheritdoc}
     */
    public function getActionGroups(string $action): array
    {
        return $this->configProvider->getGroups($action);
    }

    protected function createProcessorIterator(ContextInterface $context): ProcessorIterator
    {
        return $this->processorIteratorFactory->createProcessorIterator(
            $this->getActionProcessors($context->getAction()),
            $context,
            $this->processorApplicableChecker,
            $this->processorRegistry
        );
    }

    /**
     * @param string $action
     *
     * @return array [[processor id, [attribute name => attribute value, ...]]
     */
    protected function getActionProcessors(string $action): array
    {
        return $this->configProvider->getProcessors($action);
    }

    /**
     * Makes sure that the processor applicable checker is initialized
     */
    protected function ensureProcessorApplicableCheckerInitialized(): void
    {
        if (null === $this->processorApplicableChecker) {
            $this->initializeProcessorApplicableChecker();
        }
    }

    /**
     * Initializes $this->processorApplicableChecker
     */
    protected function initializeProcessorApplicableChecker(): void
    {
        $this->processorApplicableChecker = $this->applicableCheckerFactory->createApplicableChecker();
        $this->initializeApplicableChecker($this->processorApplicableChecker);
    }

    /**
     * Initializes the given applicable checker
     */
    protected function initializeApplicableChecker(ChainApplicableChecker $applicableChecker): void
    {
        if (!empty($this->additionalApplicableCheckers)) {
            $checkers = $this->additionalApplicableCheckers;
            krsort($checkers);
            $checkers = array_merge(...array_values($checkers));
            foreach ($checkers as $checker) {
                $applicableChecker->addChecker($checker);
            }
        }
        foreach ($applicableChecker as $checker) {
            // add the "priority" attribute to the ignore list,
            // as it is added by LoadProcessorsCompilerPass to processors' attributes only in debug mode
            if ($this->debug && $checker instanceof MatchApplicableChecker) {
                $checker->addIgnoredAttribute('priority');
            }
            if ($checker instanceof ProcessorBagAwareApplicableCheckerInterface) {
                $checker->setProcessorBag($this);
            }
        }
    }
}

<?php

namespace Oro\Component\ChainProcessor;

/**
 * The default implementation of ProcessorBagInterface.
 */
class ProcessorBag implements ProcessorBagInterface
{
    /** @var ProcessorBagConfigProviderInterface */
    protected $configProvider;

    /** @var ProcessorFactoryInterface */
    protected $processorFactory;

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

    /**
     * @param ProcessorBagConfigProviderInterface             $configProvider
     * @param ProcessorFactoryInterface                       $processorFactory
     * @param bool                                            $debug
     * @param ProcessorApplicableCheckerFactoryInterface|null $applicableCheckerFactory
     * @param ProcessorIteratorFactoryInterface|null          $processorIteratorFactory
     */
    public function __construct(
        ProcessorBagConfigProviderInterface $configProvider,
        ProcessorFactoryInterface $processorFactory,
        $debug = false,
        ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory = null,
        ProcessorIteratorFactoryInterface $processorIteratorFactory = null
    ) {
        $this->configProvider = $configProvider;
        $this->processorFactory = $processorFactory;
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
    public function addApplicableChecker(ApplicableCheckerInterface $checker, $priority = 0)
    {
        $this->additionalApplicableCheckers[$priority][] = $checker;
        $this->processorApplicableChecker = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors(ContextInterface $context)
    {
        $this->ensureProcessorApplicableCheckerInitialized();

        return $this->createProcessorIterator($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        return \array_keys($this->configProvider->getProcessors());
    }

    /**
     * {@inheritdoc}
     */
    public function getActionGroups($action)
    {
        $groups = $this->configProvider->getGroups();

        return $groups[$action] ?? [];
    }

    /**
     * @param ContextInterface $context
     *
     * @return ProcessorIterator
     */
    protected function createProcessorIterator(ContextInterface $context)
    {
        $action = $context->getAction();
        $actionProcessors = [];
        $processors = $this->configProvider->getProcessors();
        if (!empty($processors[$action])) {
            $actionProcessors = $processors[$action];
        }

        return $this->processorIteratorFactory->createProcessorIterator(
            $actionProcessors,
            $context,
            $this->processorApplicableChecker,
            $this->processorFactory
        );
    }

    /**
     * Makes sure that the processor applicable checker is initialized
     */
    protected function ensureProcessorApplicableCheckerInitialized()
    {
        if (null === $this->processorApplicableChecker) {
            $this->initializeProcessorApplicableChecker();
        }
    }

    /**
     * Initializes $this->processorApplicableChecker
     */
    protected function initializeProcessorApplicableChecker()
    {
        $this->processorApplicableChecker = $this->applicableCheckerFactory->createApplicableChecker();
        $this->initializeApplicableChecker($this->processorApplicableChecker);
    }

    /**
     * Initializes the given applicable checker
     *
     * @param ChainApplicableChecker $applicableChecker
     */
    protected function initializeApplicableChecker(ChainApplicableChecker $applicableChecker)
    {
        if (!empty($this->additionalApplicableCheckers)) {
            $checkers = $this->additionalApplicableCheckers;
            \krsort($checkers);
            $checkers = \array_merge(...$checkers);
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

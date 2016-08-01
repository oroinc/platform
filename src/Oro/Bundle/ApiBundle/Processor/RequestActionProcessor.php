<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class RequestActionProcessor extends ActionProcessor implements LoggerAwareInterface
{
    const NORMALIZE_RESULT_GROUP = 'normalize_result';

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ProcessorBagInterface $processorBag
     * @param string                $action
     * @param ConfigProvider        $configProvider
     * @param MetadataProvider      $metadataProvider
     */
    public function __construct(
        ProcessorBagInterface $processorBag,
        $action,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider
    ) {
        parent::__construct($processorBag, $action);

        $this->configProvider   = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeProcessors(ComponentContextInterface $context)
    {
        /** @var Context $context */

        $processors = $this->processorBag->getProcessors($context);
        $processorId = null;

        try {
            /** @var ProcessorInterface $processor */
            foreach ($processors as $processor) {
                if ($context->hasErrors() && self::NORMALIZE_RESULT_GROUP !== $processors->getGroup()) {
                    if ($context->getLastGroup()) {
                        // stop execution in case if the "normalize_result" group is disabled
                        throw $this->buildErrorException($context->getErrors());
                    }
                    // go to the "normalize_result" group
                    $this->executeNormalizeResultProcessors($context);
                    break;
                } else {
                    $processorId = $processors->getProcessorId();
                    $processor->process($context);
                }
            }
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    sprintf('The execution of "%s" processor is failed.', $processorId),
                    ['exception' => $e]
                );
            }

            // rethrow an exception occurred in any processor from the "normalize_result" group,
            // this is required to prevent circular handling of such exception
            // also rethrow an exception in case if the "normalize_result" group is disabled
            if (self::NORMALIZE_RESULT_GROUP === $processors->getGroup() || $context->getLastGroup()) {
                throw $e;
            }

            // add an error to the context
            $context->addError(Error::createByException($e));

            // go to the "normalize_result" group
            $this->executeNormalizeResultProcessors($context);
        }
    }

    /**
     * Executes processors from the "normalize_result" group.
     * These processors are intended to prepare valid response, regardless whether an error occurred or not.
     *
     * @param ComponentContextInterface $context
     */
    protected function executeNormalizeResultProcessors(ComponentContextInterface $context)
    {
        $context->setFirstGroup(self::NORMALIZE_RESULT_GROUP);
        $processors = $this->processorBag->getProcessors($context);
        /** @var ProcessorInterface $processor */
        foreach ($processors as $processor) {
            try {
                $processor->process($context);
            } catch (\Exception $e) {
                if (null !== $this->logger) {
                    $this->logger->error(
                        sprintf('The execution of "%s" processor is failed.', $processors->getProcessorId()),
                        ['exception' => $e]
                    );
                }

                throw $e;
            }
        }
    }

    /**
     * @param Error[] $errors
     *
     * @return \Exception
     */
    protected function buildErrorException(array $errors)
    {
        /** @var Error $firstError */
        $firstError = reset($errors);
        $exception = $firstError->getInnerException();
        if (null === $exception) {
            $exceptionMessage = sprintf('An unexpected error occurred: %s.', $firstError->getTitle());
            $detail = $firstError->getDetail();
            if ($detail) {
                $exceptionMessage .= ' ' . $detail;
            }
            $exception = new RuntimeException($exceptionMessage);
        }

        return $exception;
    }
}

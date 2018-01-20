<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Exception\ValidationExceptionInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;

class NormalizeResultActionProcessor extends ActionProcessor implements LoggerAwareInterface
{
    const NORMALIZE_RESULT_GROUP = 'normalize_result';

    /** @var LoggerInterface */
    protected $logger;

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
        /** @var NormalizeResultContext $context */

        $processors = $this->processorBag->getProcessors($context);
        $processorId = null;
        $group = null;
        try {
            $errorsHandled = false;
            /** @var ProcessorInterface $processor */
            foreach ($processors as $processor) {
                if ($context->hasErrors()) {
                    $errorsHandled = true;
                    if (self::NORMALIZE_RESULT_GROUP !== $group) {
                        $this->handleErrors($context, $processorId, $group);
                        break;
                    }
                }
                $processorId = $processors->getProcessorId();
                $group = $processors->getGroup();
                $processor->process($context);
            }
            if (!$errorsHandled && $context->hasErrors()) {
                $this->handleErrors($context, $processorId, $group);
            }
        } catch (\Error $e) {
            $this->handleException(
                new \ErrorException($e->getMessage(), $e->getCode(), E_ERROR, $e->getFile(), $e->getLine()),
                $context,
                $processorId,
                $group
            );
        } catch (\Exception $e) {
            $this->handleException($e, $context, $processorId, $group);
        }
    }

    /**
     * @param NormalizeResultContext $context
     * @param string                 $processorId
     * @param string|null            $group
     *
     * @throws \Exception if soft errors handling was not requested
     */
    protected function handleErrors(NormalizeResultContext $context, $processorId, $group)
    {
        if ($this->isNormalizeResultEnabled($context)) {
            // go to the "normalize_result" group
            $this->executeNormalizeResultProcessors($context);
        } elseif (!$context->isSoftErrorsHandling()) {
            throw $this->buildErrorException($context->getErrors());
        }
    }

    /**
     * @param \Exception             $e
     * @param NormalizeResultContext $context
     * @param string                 $processorId
     * @param string|null            $group
     *
     * @throws \Exception if soft errors handling was not requested
     */
    protected function handleException(\Exception $e, NormalizeResultContext $context, $processorId, $group)
    {
        if (null !== $this->logger) {
            $this->logException($e, $processorId, $context);
        }

        if (self::NORMALIZE_RESULT_GROUP === $group || !$this->isNormalizeResultEnabled($context)) {
            // rethrow an exception occurred in any processor from the "normalize_result" group,
            // this is required to prevent circular handling of such exception
            // also rethrow an exception in case if the "normalize_result" group is disabled
            if (!$context->isSoftErrorsHandling()) {
                throw $e;
            }
            // in case if soft errors handling is enabled just add an error to the context
            $context->addError(Error::createByException($e));
        } else {
            // add an error to the context
            $context->addError(Error::createByException($e));
            // go to the "normalize_result" group
            $this->executeNormalizeResultProcessors($context);
        }
    }

    /**
     * @param \Exception                $e
     * @param string                    $processorId
     * @param ComponentContextInterface $context
     */
    protected function logException(\Exception $e, string $processorId, ComponentContextInterface $context)
    {
        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($e);
        if ($underlyingException instanceof ValidationExceptionInterface) {
            return;
        }

        if ($context instanceof NormalizeResultContext && $context->isSoftErrorsHandling()) {
            $this->logger->warning(
                sprintf('An exception occurred in "%s" processor.', $processorId),
                array_merge(['exception' => $e], $this->getLogContext($context))
            );
        } else {
            $this->logger->error(
                sprintf('The execution of "%s" processor is failed.', $processorId),
                array_merge(['exception' => $e], $this->getLogContext($context))
            );
        }
    }

    /**
     * @param ComponentContextInterface $context
     *
     * @return bool
     */
    protected function isNormalizeResultEnabled(ComponentContextInterface $context)
    {
        return !$context->getLastGroup();
    }

    /**
     * Executes processors from the "normalize_result" group.
     * These processors are intended to prepare valid response, regardless whether an error occurred or not.
     *
     * @param ComponentContextInterface $context
     *
     * @throws \Exception if some processor throws an exception
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
                        array_merge(['exception' => $e], $this->getLogContext($context))
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

    /**
     * @param ComponentContextInterface $context
     *
     * @return array
     */
    protected function getLogContext(ComponentContextInterface $context)
    {
        $result = ['action' => $context->getAction()];
        if ($context instanceof ApiContext) {
            $result['requestType'] = (string)$context->getRequestType();
            $result['version'] = $context->getVersion();
        }
        if ($context instanceof Context) {
            $result['class'] = $context->getClassName();
        }
        if ($context instanceof SingleItemContext) {
            $result['id'] = $context->getId();
        }

        return $result;
    }
}

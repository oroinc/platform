<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Exception\ValidationExceptionInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * The base processor for actions with "normalize_result" group.
 * Processors from this group are intended to prepare a valid response
 * and they are executed regardless whether an error occurred or not.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NormalizeResultActionProcessor extends ActionProcessor implements LoggerAwareInterface
{
    protected ?LoggerInterface $logger = null;

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeProcessors(ComponentContextInterface $context): void
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
                    if (ApiActionGroup::NORMALIZE_RESULT !== $group) {
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
            $this->handlePhpError($e, $context, $processorId, $group);
        } catch (\Exception $e) {
            $this->handleException($e, $context, $processorId, $group);
        }
    }

    /**
     * @throws \Exception if the soft handling of errors was not requested
     */
    protected function handleErrors(NormalizeResultContext $context, string $processorId, ?string $group): void
    {
        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('Error(s) occurred in "%s" processor.', $processorId),
                array_merge(
                    ['errors' => $this->getErrorsForLog($context->getErrors())],
                    $this->getLogContext($context)
                )
            );
        }

        if ($this->isNormalizeResultEnabled($context)) {
            // go to the "normalize_result" group
            $this->executeNormalizeResultProcessors($context);
        } elseif (!$context->isSoftErrorsHandling()) {
            throw new UnhandledErrorsException($context->getErrors());
        }
    }

    /**
     * @throws \Exception if the soft handling of errors was not requested
     */
    protected function handlePhpError(
        \Error $e,
        NormalizeResultContext $context,
        string $processorId,
        ?string $group
    ): void {
        $this->handleException(
            new \ErrorException($e->getMessage(), $e->getCode(), E_ERROR, $e->getFile(), $e->getLine()),
            $context,
            $processorId,
            $group
        );
    }

    /**
     * @throws \Exception if the soft handling of errors was not requested
     */
    protected function handleException(
        \Exception $e,
        NormalizeResultContext $context,
        string $processorId,
        ?string $group
    ): void {
        if (null !== $this->logger) {
            $this->logException($e, $processorId, $context);
        }

        if (ApiActionGroup::NORMALIZE_RESULT === $group || !$this->isNormalizeResultEnabled($context)) {
            // rethrow an exception occurred in any processor from the "normalize_result" group
            // or if the "normalize_result" group is disabled,
            // it is required to prevent circular handling of such exception
            if (!$context->isSoftErrorsHandling()) {
                throw $e;
            }
            // if the soft handling of errors is enabled, just add an error to the context
            $context->addError(Error::createByException($e));
        } else {
            // add an error to the context
            $context->addError(Error::createByException($e));
            // go to the "normalize_result" group
            $this->executeNormalizeResultProcessors($context);
        }
    }

    protected function logException(\Exception $e, string $processorId, NormalizeResultContext $context): void
    {
        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($e);
        if ($this->isSafeException($underlyingException)) {
            $this->logger->info(
                sprintf('An exception occurred in "%s" processor.', $processorId),
                array_merge(['exception' => $e], $this->getLogContext($context))
            );
        } elseif ($underlyingException instanceof UnhandledErrorsException) {
            $this->logger->error(
                sprintf('Unhandled error(s) occurred in "%s" processor.', $processorId),
                array_merge(
                    ['errors' => $this->getErrorsForLog($underlyingException->getErrors())],
                    $this->getLogContext($context)
                )
            );
        } elseif ($context->isSoftErrorsHandling()) {
            $this->logger->error(
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
     * Indicates whether the given exception represents an error
     * that is properly handled and the current API action response contains information about this error.
     * Actually such exceptions are an alternative for adding an error to the action context.
     * Examples of safe exceptions:
     * * invalid request data
     * * requesting not existing resource
     * * access to the requested resource is denied
     */
    protected function isSafeException(\Exception $e): bool
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode() < Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return
            $e instanceof AccessDeniedException
            || $e instanceof AuthenticationException
            || $e instanceof ValidationExceptionInterface;
    }

    protected function isNormalizeResultEnabled(NormalizeResultContext $context): bool
    {
        return !$context->getLastGroup();
    }

    /**
     * Executes processors from the "normalize_result" group.
     * These processors are intended to prepare valid response, regardless whether an error occurred or not.
     *
     * @throws \Exception if some processor throws an exception
     */
    protected function executeNormalizeResultProcessors(NormalizeResultContext $context): void
    {
        $context->setFirstGroup(ApiActionGroup::NORMALIZE_RESULT);
        $processors = $this->processorBag->getProcessors($context);
        /** @var ProcessorInterface $processor */
        foreach ($processors as $processor) {
            try {
                $processor->process($context);
            } catch (\Exception $e) {
                if (null !== $this->logger && !$e instanceof AuthenticationException) {
                    $this->logger->error(
                        sprintf('The execution of "%s" processor is failed.', $processors->getProcessorId()),
                        array_merge(['exception' => $e], $this->getLogContext($context))
                    );
                }

                throw $e;
            }
        }
    }

    protected function getLogContext(NormalizeResultContext $context): array
    {
        $result = [
            'action'      => $context->getAction(),
            'requestType' => (string)$context->getRequestType(),
            'version'     => $context->getVersion()
        ];
        if ($context instanceof Context) {
            $result['class'] = $context->getClassName();
        }
        if ($context instanceof SingleItemContext) {
            $result['id'] = $context->getId();
        }

        return $result;
    }

    /**
     * @param Error[] $errors
     *
     * @return array
     */
    private function getErrorsForLog(array $errors): array
    {
        return array_map(
            function (Error $error) {
                return $this->getErrorForLog($error);
            },
            $errors
        );
    }

    private function getErrorForLog(Error $error): array
    {
        $result = [];
        $title = $this->getErrorTextPropertyForLog($error->getTitle());
        if ($title) {
            $result['title'] = $title;
        }
        $detail = $this->getErrorTextPropertyForLog($error->getDetail());
        if ($detail) {
            $result['detail'] = $detail;
        }
        $statusCode = $error->getStatusCode();
        if ($statusCode) {
            $result['statusCode'] = $statusCode;
        }
        $code = $error->getCode();
        if ($code) {
            $result['code'] = $code;
        }
        $exception = $error->getInnerException();
        if (null !== $exception) {
            $result['exception'] = sprintf('%s: %s', \get_class($exception), $exception->getMessage());
        }
        $source = $error->getSource();
        if (null !== $source) {
            $result = array_merge($result, $this->getErrorSourceForLog($source));
        }

        return $result;
    }

    private function getErrorTextPropertyForLog(string|Label|null $value): ?string
    {
        if ($value instanceof Label) {
            $value = $value->getName();
        }

        return $value;
    }

    private function getErrorSourceForLog(ErrorSource $errorSource): array
    {
        $result = [];
        $parameter = $errorSource->getParameter();
        if ($parameter) {
            $result['source.parameter'] = $parameter;
        }
        $pointer = $errorSource->getPointer();
        if ($pointer) {
            $result['source.pointer'] = $pointer;
        }
        $propertyPath = $errorSource->getPropertyPath();
        if ($propertyPath) {
            $result['source.propertyPath'] = $propertyPath;
        }

        return $result;
    }
}

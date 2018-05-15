<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for processors responsible to build a response using the response document builder
 * and add the filled document builder to the context
 */
abstract class BuildResultDocument implements ProcessorInterface
{
    /** @var DocumentBuilderFactory */
    protected $documentBuilderFactory;

    /** @var ErrorCompleterRegistry */
    protected $errorCompleterRegistry;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param DocumentBuilderFactory $documentBuilderFactory
     * @param ErrorCompleterRegistry $errorCompleterRegistry
     * @param LoggerInterface        $logger
     */
    public function __construct(
        DocumentBuilderFactory $documentBuilderFactory,
        ErrorCompleterRegistry $errorCompleterRegistry,
        LoggerInterface $logger
    ) {
        $this->documentBuilderFactory = $documentBuilderFactory;
        $this->errorCompleterRegistry = $errorCompleterRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasErrors()) {
            $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder($context->getRequestType());
            try {
                $documentBuilder->setErrorCollection($context->getErrors());
                // remove errors from the context to avoid processing them by other processors
                $context->resetErrors();
            } catch (\Exception $e) {
                $this->processException($documentBuilder, $context, $e);
                $context->resetErrors();
            }
            $context->setResponseDocumentBuilder($documentBuilder);
        } elseif ($context->hasResult()) {
            $responseStatusCode = $context->getResponseStatusCode();
            if (null === $responseStatusCode || $responseStatusCode < Response::HTTP_BAD_REQUEST) {
                $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder($context->getRequestType());
                try {
                    $this->processResult($documentBuilder, $context);
                } catch (\Exception $e) {
                    $this->processException($documentBuilder, $context, $e);
                }
                $context->setResponseDocumentBuilder($documentBuilder);
            }
        }
        $context->removeResult();
    }

    /**
     * @param DocumentBuilderInterface $documentBuilder
     * @param Context                  $context
     * @param \Exception               $e
     */
    protected function processException(DocumentBuilderInterface $documentBuilder, Context $context, \Exception $e)
    {
        $context->setResponseStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $error = Error::createByException($e);
        $this->errorCompleterRegistry->getErrorCompleter($context->getRequestType())
            ->complete($error, $context->getRequestType());
        $documentBuilder->clear();
        $documentBuilder->setErrorObject($error);

        $this->logger->error(
            sprintf('Building of the result document failed.'),
            [
                'exception' => $e,
                'action'    => $context->getAction(),
                'entity'    => $context->getClassName()
            ]
        );
    }

    /**
     * @param DocumentBuilderInterface $documentBuilder
     * @param Context                  $context
     */
    abstract protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context);
}

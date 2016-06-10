<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Model\Error;

abstract class BuildResultDocument implements ProcessorInterface
{
    /** @var DocumentBuilderInterface */
    protected $documentBuilder;

    /** @var ErrorCompleterInterface */
    protected $errorCompleter;

    /**
     * @param DocumentBuilderInterface $documentBuilder
     * @param ErrorCompleterInterface  $errorCompleter
     */
    public function __construct(
        DocumentBuilderInterface $documentBuilder,
        ErrorCompleterInterface $errorCompleter
    ) {
        $this->documentBuilder = $documentBuilder;
        $this->errorCompleter = $errorCompleter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasErrors()) {
            try {
                $this->documentBuilder->setErrorCollection($context->getErrors(), $context->getMetadata());
                // remove errors from the Context to avoid processing them by other processors
                $context->resetErrors();
            } catch (\Exception $e) {
                $this->processException($context, $e);
                $context->resetErrors();
            }
        } elseif ($context->hasResult()) {
            try {
                $this->processResult($context);
            } catch (\Exception $e) {
                $this->processException($context, $e);
            }
        }

        $context->setResult($this->documentBuilder->getDocument());
    }

    /**
     * @param Context    $context
     * @param \Exception $e
     */
    protected function processException(Context $context, \Exception $e)
    {
        $context->setResponseStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $error = Error::createByException($e);
        $this->errorCompleter->complete($error);
        $this->documentBuilder->clear();
        $this->documentBuilder->setErrorObject($error);
    }

    /**
     * @param Context $context
     */
    abstract protected function processResult(Context $context);
}

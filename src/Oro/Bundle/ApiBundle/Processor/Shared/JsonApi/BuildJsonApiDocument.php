<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory;

abstract class BuildJsonApiDocument implements ProcessorInterface
{
    /** @var JsonApiDocumentBuilderFactory */
    protected $documentBuilderFactory;

    /**
     * @param JsonApiDocumentBuilderFactory $documentBuilderFactory
     */
    public function __construct(JsonApiDocumentBuilderFactory $documentBuilderFactory)
    {
        $this->documentBuilderFactory = $documentBuilderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder();

        if ($context->hasErrors()) {
            try {
                $documentBuilder->setErrorCollection($context->getErrors(), $context->getMetadata());
                // remove errors from the Context to avoid processing them by other processors
                $context->resetErrors();
            } catch (\Exception $e) {
                $this->processException($context, $e);
                $context->resetErrors();
            }
        } elseif ($context->hasResult()) {
            try {
                $this->processResult($context, $documentBuilder);
            } catch (\Exception $e) {
                $this->processException($context, $e);
            }
        }

        $context->setResult($documentBuilder->getDocument());
    }

    /**
     * @param Context    $context
     * @param \Exception $e
     */
    protected function processException(Context $context, \Exception $e)
    {
        $context->setResponseStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $error = new Error();
        $error->setInnerException($e);
        $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder();
        $documentBuilder->setErrorObject($error, $context->getMetadata());
    }

    /**
     * @param Context                $context
     * @param JsonApiDocumentBuilder $documentBuilder
     */
    abstract protected function processResult(Context $context, JsonApiDocumentBuilder $documentBuilder);
}

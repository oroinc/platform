<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete\JsonApi;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory;

class BuildJsonApiDocument implements ProcessorInterface
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
        /** @var GetContext $context */

        $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder();

        try {
            // at this step, context must not contain result.
            if ($context->hasResult() && !$context->hasErrors()) {
                throw new \Exception('Record was not deleted.');
            }

            if ($context->hasErrors()) {
                $documentBuilder->setErrorCollection($context->getErrors());
                // remove errors from the Context to avoid processing them by other processors
                $context->resetErrors();
            }

            $context->setResult($documentBuilder->getDocument());
        } catch (\Exception $e) {
            $context->setResponseStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $error = new Error();
            $error->setInnerException($e);
            $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder();
            $documentBuilder->setErrorObject($error);
            $context->setResult($documentBuilder->getDocument());
            $context->resetErrors();
        }
    }
}

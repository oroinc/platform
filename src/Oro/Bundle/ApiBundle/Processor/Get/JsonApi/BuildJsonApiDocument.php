<?php

namespace Oro\Bundle\ApiBundle\Processor\Get\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory;

/**
 * Builds JSON API response based on the Context state.
 */
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
            if ($context->hasErrors()) {
                $documentBuilder->setErrors($context->getErrors());
                // remove errors from the Context to avoid processing them by other processors
                $context->resetErrors();
            } elseif ($context->hasResult()) {
                $result = $context->getResult();
                if (null === $result) {
                    $documentBuilder->setDataObject($result);
                } else {
                    $documentBuilder->setDataObject($result, $context->getMetadata());
                }
            }

            $context->setResult($documentBuilder->getDocument());
        } catch (\Exception $e) {
            $context->setResponseStatusCode(500);
            throw $e;
        }
    }
}

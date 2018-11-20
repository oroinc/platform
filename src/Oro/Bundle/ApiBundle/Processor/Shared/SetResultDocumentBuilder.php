<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates the response document builder and adds it to the context.
 */
class SetResultDocumentBuilder implements ProcessorInterface
{
    /** @var DocumentBuilderFactory */
    private $documentBuilderFactory;

    /**
     * @param DocumentBuilderFactory $documentBuilderFactory
     */
    public function __construct(DocumentBuilderFactory $documentBuilderFactory)
    {
        $this->documentBuilderFactory = $documentBuilderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null !== $documentBuilder) {
            // the response document builder was already added to the context
            return;
        }

        $isDocumentBuilderRequired = false;
        if ($context->hasErrors()) {
            $isDocumentBuilderRequired = true;
        } elseif ($context->hasResult()) {
            $responseStatusCode = $context->getResponseStatusCode();
            $isDocumentBuilderRequired =
                null === $responseStatusCode
                || $responseStatusCode < Response::HTTP_BAD_REQUEST;
        }

        if ($isDocumentBuilderRequired) {
            $context->setResponseDocumentBuilder(
                $this->documentBuilderFactory->createDocumentBuilder($context->getRequestType())
            );
        }
    }
}

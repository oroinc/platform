<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * The class that helps to process different placeholders in a text.
 */
class DescriptionProcessor
{
    private RequestDependedTextProcessor $requestDependedTextProcessor;

    public function __construct(RequestDependedTextProcessor $requestDependedTextProcessor)
    {
        $this->requestDependedTextProcessor = $requestDependedTextProcessor;
    }

    public function process(string $description, RequestType $requestType): string
    {
        return $this->requestDependedTextProcessor->process($description, $requestType);
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * The class that helps to process "{@request:...}" placeholders in a text.
 */
class RequestDependedTextProcessor extends AbstractTextProcessor
{
    private const START_REQUEST_TAG = '{@request:';
    private const END_REQUEST_TAG   = '{@/request}';

    /**
     * Checks whether the given text contains "{@request:...}" placeholders and, if so, do the following:
     * * replaces placeholders related to the specific request type with their content
     * * removes placeholders that are not related to the specific request type
     */
    public function process(string $text, RequestType $requestType): string
    {
        return $this->processText(
            $text,
            self::START_REQUEST_TAG,
            self::END_REQUEST_TAG,
            function ($value) use ($requestType) {
                return $requestType->contains($value);
            }
        );
    }
}

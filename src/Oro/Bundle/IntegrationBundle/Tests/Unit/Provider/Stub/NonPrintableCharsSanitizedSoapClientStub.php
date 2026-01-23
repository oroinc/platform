<?php

declare(strict_types=1);

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\IntegrationBundle\Provider\NonPrintableCharsSanitizedSoapClient;
use Oro\Bundle\IntegrationBundle\Utils\NonPrintableCharsStringSanitizer;

/**
 * Test NonPrintableCharsSanitizedSoapClient that captures method calls without real SOAP transport.
 */
class NonPrintableCharsSanitizedSoapClientStub extends NonPrintableCharsSanitizedSoapClient
{
    private ?string $mockResponse;
    private NonPrintableCharsStringSanitizer $sanitizer;

    /** @var mixed[] Arguments captured after sanitization */
    public array $capturedSanitizedArguments = [];

    public function __construct(?string $mockResponse = null)
    {
        $this->mockResponse = $mockResponse ?? "<xml>test\x00response</xml>";
        $this->sanitizer = new NonPrintableCharsStringSanitizer();

        parent::__construct(null, ['location' => 'http://test.local', 'uri' => 'urn:test']);
    }

    public function setMockResponse(?string $response): void
    {
        $this->mockResponse = $response;
    }

    #[\Override]
    public function __soapCall(
        $function_name,
        $arguments,
        $options = null,
        $input_headers = null,
        &$output_headers = null
    ): mixed {
        $this->capturedSanitizedArguments = $this->sanitizeArguments($arguments);

        // Simulate successful SOAP call
        return (object)['success' => true];
    }

    #[\Override]
    public function __doRequest(
        $request,
        $location,
        $action,
        $version,
        $one_way = 0,
        ?string $uriParserClass = null
    ): ?string {
        return $this->sanitizer->removeNonPrintableCharacters($this->mockResponse);
    }

    /**
     * Sanitizes arguments recursively, matching the parent class behavior.
     */
    private function sanitizeArguments(mixed $arguments): mixed
    {
        if (!is_array($arguments)) {
            return $arguments;
        }

        $sanitized = $arguments;
        array_walk_recursive($sanitized, function (&$item) {
            if (is_string($item)) {
                $item = $this->sanitizer->removeNonPrintableCharacters($item);
            }
        });

        return $sanitized;
    }
}

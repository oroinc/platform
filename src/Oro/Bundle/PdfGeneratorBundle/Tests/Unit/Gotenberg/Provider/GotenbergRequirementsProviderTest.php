<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Gotenberg\Provider;

use Oro\Bundle\PdfGeneratorBundle\Gotenberg\Provider\GotenbergRequirementsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Requirements\Requirement;

final class GotenbergRequirementsProviderTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    /**
     * @dataProvider provideRecommendationsData
     */
    public function testGetRecommendations(
        bool $expectedFulfilled,
        string $expectedTestMessage,
        string $expectedHelpText,
        int $statusCode,
        ?string $gotenbergApiUrl,
    ): void {
        $response = $this->createMock(ResponseInterface::class);

        $response
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->httpClient
            ->method('request')
            ->with('GET', sprintf('%s/version', $gotenbergApiUrl))
            ->willReturn($response);

        $provider = new GotenbergRequirementsProvider($this->httpClient, $gotenbergApiUrl);
        $recommendation = $this->getApiAccessibilityRecommendation($provider, $expectedTestMessage, $expectedFulfilled);

        self::assertSame($expectedFulfilled, $recommendation->isFulfilled());
        self::assertStringContainsString($expectedTestMessage, $recommendation->getTestMessage());
        self::assertStringContainsString($expectedHelpText, $recommendation->getHelpText());
    }

    public static function provideRecommendationsData(): array
    {
        return [
            'URL is not configured' => [
                'expectedFulfilled' => false,
                'expectedTestMessage' => 'Gotenberg API URL is configured',
                'expectedHelpText' => 'Please set the "ORO_PDF_GENERATOR_GOTENBERG_API_URL" environment variable ' .
                    'to enable PDF generation.',
                'statusCode' => 0,
                'gotenbergApiUrl' => null,
            ],
            'API is accessible' => [
                'expectedFulfilled' => true,
                'expectedTestMessage' => 'Gotenberg API Is Accessible',
                'expectedHelpText' => 'Gotenberg API HTTP Status: 200',
                'statusCode' => Response::HTTP_OK,
                'gotenbergApiUrl' => 'http://gotenberg.local',
            ],
            'API returns non-200 status' => [
                'expectedFulfilled' => false,
                'expectedTestMessage' => 'Gotenberg API Is Accessible',
                'expectedHelpText' => 'Gotenberg API HTTP Status: 503',
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE,
                'gotenbergApiUrl' => 'http://gotenberg.local',
            ],
        ];
    }

    public function testGetRecommendationsWhenApiThrowsTransportException(): void
    {
        $exception = new TransportException('Connection refused');

        $this->httpClient
            ->method('request')
            ->willThrowException($exception);

        $provider = new GotenbergRequirementsProvider($this->httpClient, 'http://gotenberg.local');
        $recommendation = $this->getApiAccessibilityRecommendation(
            $provider,
            'Gotenberg API Is Accessible',
            false
        );

        self::assertFalse($recommendation->isFulfilled());
        self::assertStringContainsString(
            'Failed to connect to Gotenberg API. Error: Connection refused',
            $recommendation->getHelpText()
        );
    }

    private function getApiAccessibilityRecommendation(
        GotenbergRequirementsProvider $provider,
        string $expectedTestMessage,
        bool $expectedFulfilled
    ): Requirement {
        $collection = $provider->getRecommendations();
        $requirements = $collection->all();

        return array_find(
            $requirements,
            static fn (Requirement $requirement) => $expectedTestMessage === $requirement->getTestMessage() &&
                $requirement->isFulfilled() === $expectedFulfilled
        );
    }
}

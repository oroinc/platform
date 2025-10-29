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
        ?string $serverVersion = null
    ): void {
        $response = $this->createMock(ResponseInterface::class);

        $response
            ->method('getStatusCode')
            ->willReturn($statusCode);

        if ($serverVersion !== null && $statusCode === Response::HTTP_OK) {
            $response
                ->method('getContent')
                ->willReturn($serverVersion);
        }

        if ($gotenbergApiUrl !== null) {
            $this->httpClient
                ->method('request')
                ->with('GET', sprintf('%s/version', $gotenbergApiUrl))
                ->willReturn($response);
        }

        $provider = new GotenbergRequirementsProvider($this->httpClient, $gotenbergApiUrl);
        $recommendation = $this->getRecommendationByMessage($provider, $expectedTestMessage, $expectedFulfilled);

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
                'serverVersion' => null,
            ],
            'API is accessible' => [
                'expectedFulfilled' => true,
                'expectedTestMessage' => 'Gotenberg API Is Accessible',
                'expectedHelpText' => 'Gotenberg API HTTP Status: 200',
                'statusCode' => Response::HTTP_OK,
                'gotenbergApiUrl' => 'http://gotenberg.local',
                'serverVersion' => '8.5.0',
            ],
            'API returns non-200 status' => [
                'expectedFulfilled' => false,
                'expectedTestMessage' => 'Gotenberg API Is Accessible',
                'expectedHelpText' => 'Gotenberg API HTTP Status: 503',
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE,
                'gotenbergApiUrl' => 'http://gotenberg.local',
                'serverVersion' => null,
            ],
            'API version is sufficient' => [
                'expectedFulfilled' => true,
                'expectedTestMessage' => 'Connected to required Gotenberg version (8.5.0)',
                'expectedHelpText' => 'Gotenberg version must be 8.5.0 or higher',
                'statusCode' => Response::HTTP_OK,
                'gotenbergApiUrl' => 'http://gotenberg.local',
                'serverVersion' => '8.5.0',
            ],
            'API version is insufficient' => [
                'expectedFulfilled' => false,
                'expectedTestMessage' => 'Connected to required Gotenberg version (8.4.0)',
                'expectedHelpText' => 'Gotenberg version must be 8.5.0 or higher',
                'statusCode' => Response::HTTP_OK,
                'gotenbergApiUrl' => 'http://gotenberg.local',
                'serverVersion' => '8.4.0',
            ],
            'API version is invalid' => [
                'expectedFulfilled' => true,
                'expectedTestMessage' => 'Gotenberg API Is Accessible',
                'expectedHelpText' => 'Gotenberg API HTTP Status: 200',
                'statusCode' => Response::HTTP_OK,
                'gotenbergApiUrl' => 'http://gotenberg.local',
                'serverVersion' => 'invalid-version',
            ],
            'API returns 404 (old Gotenberg)' => [
                'expectedFulfilled' => false,
                'expectedTestMessage' => 'Gotenberg API Is Accessible',
                'expectedHelpText' => 'endpoint /version not found',
                'statusCode' => Response::HTTP_NOT_FOUND,
                'gotenbergApiUrl' => 'http://gotenberg.local',
                'serverVersion' => null,
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
        $recommendation = $this->getRecommendationByMessage(
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

    private function getRecommendationByMessage(
        GotenbergRequirementsProvider $provider,
        string $expectedTestMessage,
        bool $expectedFulfilled
    ): Requirement {
        $collection = $provider->getRecommendations();
        $requirements = $collection->all();

        $recommendation = array_find(
            $requirements,
            static fn (Requirement $requirement) => $expectedTestMessage === $requirement->getTestMessage() &&
                $requirement->isFulfilled() === $expectedFulfilled
        );

        if ($recommendation === null) {
            $this->fail(sprintf(
                'No recommendation found with message "%s" and fulfilled status %s',
                $expectedTestMessage,
                $expectedFulfilled
            ));
        }

        return $recommendation;
    }
}

<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SyncBundle\Authentication\Origin\ApplicationOriginProvider;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginExtractor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplicationOriginProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private OriginExtractor&MockObject $originExtractor;
    private ApplicationOriginProvider $applicationOriginProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->originExtractor = $this->createMock(OriginExtractor::class);

        $this->applicationOriginProvider = new ApplicationOriginProvider(
            $this->configManager,
            $this->originExtractor
        );
    }

    /**
     * @dataProvider getOriginsDataProvider
     */
    public function testGetOrigins(?string $origin, array $expectedOrigins): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_ui.application_url', null)
            ->willReturn($appUrl = 'sampleAppUrl');

        $this->originExtractor->expects(self::once())
            ->method('fromUrl')
            ->with($appUrl)
            ->willReturn($origin);

        self::assertEquals($expectedOrigins, $this->applicationOriginProvider->getOrigins());
    }

    public function getOriginsDataProvider(): array
    {
        return [
            'normal origin' => [
                'origin' => 'appOrigin',
                'expectedOrigins' => ['appOrigin'],
            ],
            'null origin' => [
                'origin' => null,
                'expectedOrigins' => [],
            ],
        ];
    }
}

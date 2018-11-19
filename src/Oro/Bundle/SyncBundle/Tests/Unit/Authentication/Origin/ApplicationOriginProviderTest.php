<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SyncBundle\Authentication\Origin\ApplicationOriginProvider;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginExtractor;

class ApplicationOriginProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;
    /** @var OriginExtractor|\PHPUnit\Framework\MockObject\MockObject */
    private $originExtractor;

    /** @var ApplicationOriginProvider */
    private $applicationOriginProvider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->originExtractor = $this->createMock(OriginExtractor::class);

        $this->applicationOriginProvider = new ApplicationOriginProvider($this->configManager, $this->originExtractor);
    }

    /**
     * @dataProvider getOriginsDataProvider
     *
     * @param null|string $origin
     * @param array $expectedOrigins
     */
    public function testGetOrigins(?string $origin, array $expectedOrigins): void
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_ui.application_url', null)
            ->willReturn($appUrl = 'sampleAppUrl');

        $this->originExtractor
            ->expects(self::once())
            ->method('fromUrl')
            ->with($appUrl)
            ->willReturn($origin);

        self::assertEquals($expectedOrigins, $this->applicationOriginProvider->getOrigins());
    }

    /**
     * @return array
     */
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

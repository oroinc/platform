<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\ChainPreferredLocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;

class ChainPreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProviderA;

    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProviderB;

    /** @var ChainPreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProviderA = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->innerProviderB = $this->createMock(PreferredLocalizationProviderInterface::class);

        $this->provider = new ChainPreferredLocalizationProvider([$this->innerProviderA, $this->innerProviderB]);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $isSupportedA, bool $isSupportedB, bool $isSupported): void
    {
        $entity = new \stdClass();

        $this->innerProviderA->expects($isSupportedA ? $this->once() : $this->any())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn($isSupportedA);

        $this->innerProviderB->expects($isSupportedB ? $this->once() : $this->any())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn($isSupportedB);

        $this->assertSame($isSupported, $this->provider->supports($entity));
    }

    public function supportsDataProvider(): array
    {
        return [
            'supported by A' => [
                'isSupportedA' => true,
                'isSupportedB' => false,
                'isSupported' => true,
            ],
            'supported by B' => [
                'isSupportedA' => false,
                'isSupportedB' => true,
                'isSupported' => true,
            ],
            'not supported' => [
                'isSupportedA' => false,
                'isSupportedB' => false,
                'isSupported' => false,
            ],
        ];
    }

    /**
     * @dataProvider getPreferredLocalizationDataProvider
     */
    public function testGetPreferredLocalization(
        bool $isSupportedA,
        bool $isSupportedB,
        ?Localization $localizationFromA,
        ?Localization $localizationFromB
    ): void {
        $entity = new \stdClass();

        // innerProviderA
        $this->innerProviderA->expects($isSupportedA ? $this->once() : $this->any())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn($isSupportedA);

        $this->innerProviderA->expects($isSupportedA ? $this->once() : $this->never())
            ->method('getPreferredLocalization')
            ->with($this->identicalTo($entity))
            ->willReturn($localizationFromA);

        // innerProviderB
        $this->innerProviderB->expects($isSupportedB ? $this->once() : $this->any())
            ->method('supports')
            ->with($this->identicalTo($entity))
            ->willReturn($isSupportedB);

        $this->innerProviderB->expects($isSupportedB ? $this->once() : $this->never())
            ->method('getPreferredLocalization')
            ->with($this->identicalTo($entity))
            ->willReturn($localizationFromB);

        // exception when any provider not supports entity
        if (!$isSupportedA && !$isSupportedB) {
            $this->expectException(\LogicException::class);
        }

        $this->assertSame(
            $localizationFromA ?? $localizationFromB,
            $this->provider->getPreferredLocalization($entity)
        );
    }

    public function getPreferredLocalizationDataProvider(): array
    {
        return [
            'supported by A' => [
                'isSupportedA' => true,
                'isSupportedB' => false,
                'localizationFromA' => new Localization(),
                'localizationFromB' => null,
            ],
            'supported by B' => [
                'isSupportedA' => false,
                'isSupportedB' => true,
                'localizationFromA' => null,
                'localizationFromB' => new Localization(),
            ],
            'exception when not supported' => [
                'isSupportedA' => false,
                'isSupportedB' => false,
                'localizationFromA' => null,
                'localizationFromB' => null,
            ],
        ];
    }
}

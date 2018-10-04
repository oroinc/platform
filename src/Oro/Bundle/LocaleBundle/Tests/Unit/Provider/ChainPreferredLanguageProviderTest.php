<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestCustomEntity;
use Oro\Bundle\LocaleBundle\Provider\ChainPreferredLanguageProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;

class ChainPreferredLanguageProviderTest extends \PHPUnit\Framework\TestCase
{
    private const LANGUAGE = 'fr_FR';

    /**
     * @var ChainPreferredLanguageProvider
     */
    private $chainProvider;
    
    public function setUp()
    {
        $this->chainProvider = new ChainPreferredLanguageProvider();
    }

    public function testSupports(): void
    {
        $testEntity = new TestCustomEntity();

        $this->chainProvider->addProvider($this->mockProvider(false, $testEntity));
        $this->chainProvider->addProvider($this->mockProvider(true, $testEntity));

        self::assertTrue($this->chainProvider->supports($testEntity));
    }

    public function testSupportsWhenNoProviderSupports(): void
    {
        $testEntity = new TestCustomEntity();

        $this->chainProvider->addProvider($this->mockProvider(false, $testEntity));

        self::assertFalse($this->chainProvider->supports($testEntity));
    }

    public function testGetPreferredLanguage(): void
    {
        $testEntity = new TestCustomEntity();
        $notSupportProvider = $this->mockProvider(false, $testEntity);
        $notSupportProvider
            ->expects($this->never())
            ->method('getPreferredLanguage');


        $supportProvider = $this->mockProvider(true, $testEntity);
        $supportProvider
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with($testEntity)
            ->willReturn(self::LANGUAGE);

        $this->chainProvider->addProvider($notSupportProvider);
        $this->chainProvider->addProvider($supportProvider);

        self::assertEquals(self::LANGUAGE, $this->chainProvider->getPreferredLanguage($testEntity));
    }

    public function testGetPreferredLanguageWhenNoProvidersSupport(): void
    {
        $testEntity = new TestCustomEntity();
        $provider = $this->mockProvider(false, $testEntity);

        $this->chainProvider->addProvider($provider);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('No preferred language provider for the "%s" entity class exists', TestCustomEntity::class)
        );

        $this->chainProvider->getPreferredLanguage($testEntity);
    }

    /**
     * @param bool $support
     * @param object $testEntity
     * @return PreferredLanguageProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockProvider(bool $support, $testEntity): PreferredLanguageProviderInterface
    {
        $provider = $this->createMock(PreferredLanguageProviderInterface::class);
        $provider
            ->expects($this->any())
            ->method('supports')
            ->with($testEntity)
            ->willReturn($support);

        return $provider;
    }
}

<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmail;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\DefaultPreferredLanguageProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

class DefaultPreferredLanguageProviderTest extends \PHPUnit\Framework\TestCase
{
    private const LANGUAGE = 'fr_FR';

    /**
     * @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localeSettings;

    /**
     * @var DefaultPreferredLanguageProvider
     */
    private $provider;
    
    public function setUp()
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->provider = new DefaultPreferredLanguageProvider($this->localeSettings);
    }

    /**
     * @dataProvider entitiesDataProvider
     * @param object $entity
     */
    public function testSupports($entity): void
    {
        self::assertTrue($this->provider->supports($entity));
    }

    /**
     * @dataProvider entitiesDataProvider
     * @param object $entity
     */
    public function testGetPreferredLanguage($entity): void
    {
        $this->localeSettings
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn(self::LANGUAGE);

        self::assertEquals(self::LANGUAGE, $this->provider->getPreferredLanguage($entity));
    }

    /**
     * @return array
     */
    public function entitiesDataProvider(): array
    {
        return [
            [new \stdClass()],
            [new TestProduct()],
            [new TestEmail()]
        ];
    }
}

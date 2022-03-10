<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationEntityNameProvider;

class LocalizationEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private LocalizationEntityNameProvider $provider;

    private Localization $localization;

    protected function setUp(): void
    {
        $this->provider = new LocalizationEntityNameProvider();

        $this->localization = new Localization();
        $this->localization->setName('test name');
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        self::assertEquals(
            $this->localization->getName(),
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $this->localization)
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', Localization::class, 'campaign')
        );
    }
}

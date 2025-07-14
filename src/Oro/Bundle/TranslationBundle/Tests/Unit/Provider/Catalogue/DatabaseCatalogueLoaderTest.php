<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider\Catalogue;

use Oro\Bundle\TranslationBundle\Provider\Catalogue\DatabaseCatalogueLoader;
use Oro\Bundle\TranslationBundle\Provider\TranslationProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseCatalogueLoaderTest extends TestCase
{
    public function testGetLoaderName(): void
    {
        $loader = new DatabaseCatalogueLoader($this->createMock(TranslationProvider::class));
        self::assertEquals('database', $loader->getLoaderName());
    }

    public function testGetCatalogue(): void
    {
        $expectedResult = new MessageCatalogue('en_EN', []);
        $translationProvider = $this->createMock(TranslationProvider::class);
        $translationProvider->expects(self::once())
            ->method('getMessageCatalogueByLocaleAndScope')
            ->with('en_EN')
            ->willReturn($expectedResult);

        $loader = new DatabaseCatalogueLoader($translationProvider);
        self::assertSame($expectedResult, $loader->getCatalogue('en_EN'));
    }
}

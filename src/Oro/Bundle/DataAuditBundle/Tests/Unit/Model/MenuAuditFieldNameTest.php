<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditFieldName;
use PHPUnit\Framework\TestCase;

class MenuAuditFieldNameTest extends TestCase
{
    public function testAPlainProperty(): void
    {
        $field = new MenuAuditFieldName('uri');

        self::assertSame('uri', $field->getProperty());
        self::assertNull($field->getLocalization());
        self::assertSame('uri', (string)$field);
    }

    public function testALocalizedProperty(): void
    {
        $field = new MenuAuditFieldName('titles', 'German');

        self::assertSame('titles', $field->getProperty());
        self::assertSame('German', $field->getLocalization());
        self::assertSame('titles|German', (string)$field);
    }

    public function testTheDefaultLocalizationIsNoLocalization(): void
    {
        self::assertSame('titles', (string)new MenuAuditFieldName('titles', ''));
        self::assertNull((new MenuAuditFieldName('titles', ''))->getLocalization());
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParse(string $field, string $property, ?string $localization): void
    {
        $parsed = MenuAuditFieldName::parse($field);

        self::assertSame($property, $parsed->getProperty());
        self::assertSame($localization, $parsed->getLocalization());
        self::assertSame($field, (string)$parsed);
    }

    public function parseDataProvider(): array
    {
        return [
            'a property' => ['uri', 'uri', null],
            'a localized property' => ['titles|German', 'titles', 'German'],
            'a localization named after a separator' => ['titles|German|Austria', 'titles', 'German|Austria'],
        ];
    }
}

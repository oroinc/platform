<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Tools;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationWithTagsData;
use Oro\Bundle\LocaleBundle\Tools\LocalizedFieldsSanitizer;
use Oro\Bundle\SecurityBundle\Tools\AbstractFieldsSanitizer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class LocalizedFieldsSanitizerTest extends WebTestCase
{
    private LocalizedFieldsSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadLocalizationWithTagsData::class]);

        $this->sanitizer = self::getContainer()->get('oro_locale.tools.localized_fields_sanitizer');
    }

    public function testSanitizeByFieldTypeWhenNoEntityManager(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Entity manager for class %s was not found', \stdClass::class));

        $result = $this->sanitizer->sanitizeByFieldType(
            \stdClass::class,
            'sampleField',
            AbstractFieldsSanitizer::MODE_STRIP_TAGS,
            [],
            false
        );
        iterator_to_array($result);
    }

    public function testSanitizeByFieldTypeWhenNoField(): void
    {
        $result = $this->sanitizer->sanitizeByFieldType(
            Localization::class,
            'missing',
            AbstractFieldsSanitizer::MODE_STRIP_TAGS,
            [],
            false
        );

        self::assertEquals([], iterator_to_array($result));
    }

    /**
     * @dataProvider modeDataProvider
     *
     * @param int $mode
     */
    public function testSanitizeByFieldTypeWhenNotApplyChanges(int $mode): void
    {
        $result = $this->sanitizer->sanitizeByFieldType(Localization::class, Types::STRING, $mode, [], false);

        $localization = $this->getReference(LoadLocalizationWithTagsData::LOCALIZATION_WITH_TAG_1);
        self::assertEquals([$localization->getId() => ['titles']], iterator_to_array($result));
    }

    public function modeDataProvider(): array
    {
        return [
            ['mode' => AbstractFieldsSanitizer::MODE_STRIP_TAGS],
            ['mode' => AbstractFieldsSanitizer::MODE_SANITIZE],
        ];
    }

    /**
     * @dataProvider sanitizeByFieldTypeWhenApplyChangesDataProvider
     *
     * @param int $mode
     */
    public function testSanitizeByFieldTypeWhenApplyChanges(int $mode, string $expected): void
    {
        $result = $this->sanitizer->sanitizeByFieldType(Localization::class, Types::STRING, $mode, [], true);

        $localization = $this->getReference(LoadLocalizationWithTagsData::LOCALIZATION_WITH_TAG_1);
        self::assertEquals([$localization->getId() => ['titles']], iterator_to_array($result));
        self::assertEquals($expected, $localization->getDefaultTitle());
    }

    public function sanitizeByFieldTypeWhenApplyChangesDataProvider(): array
    {
        return [
            ['mode' => AbstractFieldsSanitizer::MODE_STRIP_TAGS, 'expected' => 'Name with'],
            ['mode' => AbstractFieldsSanitizer::MODE_SANITIZE, 'expected' => 'Name with Le&gt;'],
        ];
    }
}

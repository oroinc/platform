<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Tools;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadTestActivityTargetWithTagsData;
use Oro\Bundle\SecurityBundle\Tools\AbstractFieldsSanitizer;
use Oro\Bundle\SecurityBundle\Tools\RegularFieldsSanitizer;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class RegularFieldsSanitizerTest extends WebTestCase
{
    private RegularFieldsSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadTestActivityTargetWithTagsData::class]);

        $this->sanitizer = self::getContainer()->get('oro_security.tools.regular_fields_sanitizer');
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
            TestActivityTarget::class,
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
        $result = $this->sanitizer->sanitizeByFieldType(TestActivityTarget::class, Types::STRING, $mode, [], false);

        $product1 = $this->getReference(LoadTestActivityTargetWithTagsData::ACTIVITY_TARGET_1);
        self::assertEquals([$product1->getId() => ['string']], iterator_to_array($result));
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
        $result = $this->sanitizer->sanitizeByFieldType(TestActivityTarget::class, Types::STRING, $mode, [], true);

        $product1 = $this->getReference(LoadTestActivityTargetWithTagsData::ACTIVITY_TARGET_1);
        self::assertEquals([$product1->getId() => ['string']], iterator_to_array($result));
        self::assertEquals($expected, $product1->getString());
    }

    public function sanitizeByFieldTypeWhenApplyChangesDataProvider(): array
    {
        return [
            ['mode' => AbstractFieldsSanitizer::MODE_STRIP_TAGS, 'expected' => 'Name with'],
            ['mode' => AbstractFieldsSanitizer::MODE_SANITIZE, 'expected' => 'Name with Le&gt;'],
        ];
    }
}

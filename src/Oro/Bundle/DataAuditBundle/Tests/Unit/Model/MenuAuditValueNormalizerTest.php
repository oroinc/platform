<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditValueNormalizer;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuAuditValueNormalizerTest extends TestCase
{
    private EntityNameResolver&MockObject $entityNameResolver;
    private MenuAuditValueNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->normalizer = new MenuAuditValueNormalizer($this->entityNameResolver);
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testKeepsTheTypeOfAValue(mixed $old, mixed $new, array $expected): void
    {
        self::assertSame($expected, $this->normalizer->normalize($old, $new));
    }

    public function valueDataProvider(): array
    {
        return [
            'a flag stays a flag' => [
                'old' => true,
                'new' => false,
                'expected' => ['type' => 'boolean', 'old' => true, 'new' => false],
            ],
            'a number stays a number' => [
                'old' => 3,
                'new' => 1,
                'expected' => ['type' => 'integer', 'old' => 3, 'new' => 1],
            ],
            'text stays text' => [
                'old' => '/contact',
                'new' => '/contact-us',
                'expected' => ['type' => 'text', 'old' => '/contact', 'new' => '/contact-us'],
            ],
            'an added value has no previous one' => [
                'old' => null,
                'new' => '/terms',
                'expected' => ['type' => 'text', 'old' => null, 'new' => '/terms'],
            ],
            'an emptied value has no current one' => [
                'old' => '/terms',
                'new' => '',
                'expected' => ['type' => 'text', 'old' => '/terms', 'new' => null],
            ],
            'a list is read as a list' => [
                'old' => [],
                'new' => ['mobile', 'tablet'],
                'expected' => ['type' => 'text', 'old' => null, 'new' => 'mobile, tablet'],
            ],
        ];
    }

    public function testALocalizedValueIsReadAsItsText(): void
    {
        $value = (new LocalizedFallbackValue())->setString('Contact Us');

        self::assertSame(
            ['type' => 'text', 'old' => null, 'new' => 'Contact Us'],
            $this->normalizer->normalize(null, $value)
        );
    }

    public function testALocalizedCollectionIsReadAsItsDefaultValue(): void
    {
        $localized = (new LocalizedFallbackValue())->setString('Kontakt');
        $localized->setLocalization(new Localization());

        $values = new ArrayCollection([$localized, (new LocalizedFallbackValue())->setString('Contact Us')]);

        self::assertSame(
            ['type' => 'text', 'old' => null, 'new' => 'Contact Us'],
            $this->normalizer->normalize(null, $values)
        );
    }

    public function testAnAssociatedRecordIsReadAsItsName(): void
    {
        $contentNode = new \stdClass();
        $this->entityNameResolver->expects(self::once())
            ->method('getName')
            ->with($contentNode)
            ->willReturn('About Us');

        self::assertSame(
            ['type' => 'text', 'old' => null, 'new' => 'About Us'],
            $this->normalizer->normalize(null, $contentNode)
        );
    }

    public function testComparesValuesByTheirNormalizedForm(): void
    {
        self::assertTrue($this->normalizer->isSame('/contact', '/contact'));
        self::assertTrue($this->normalizer->isSame(null, ''));
        self::assertTrue($this->normalizer->isSame([], null));
        self::assertTrue(
            $this->normalizer->isSame('Contact Us', (new LocalizedFallbackValue())->setString('Contact Us'))
        );
        self::assertFalse($this->normalizer->isSame('/contact', '/contact-us'));
        self::assertFalse($this->normalizer->isSame(true, false));
    }

    public function testNormalizesASingleValueTheSameWay(): void
    {
        self::assertSame('Contact Us', $this->normalizer->normalizeValue(
            (new LocalizedFallbackValue())->setString('Contact Us')
        ));
        self::assertTrue($this->normalizer->normalizeValue(true));
        self::assertNull($this->normalizer->normalizeValue(''));
    }
}

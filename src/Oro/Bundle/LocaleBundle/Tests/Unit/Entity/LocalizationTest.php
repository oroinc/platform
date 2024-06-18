<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(new Localization(), [
            ['id', 1],
            ['name', 'test_name'],
            ['language', new Language()],
            ['formattingCode', 'formatting_test_code'],
            ['rtlMode', false, true],
            ['parentLocalization', new Localization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
        self::assertPropertyCollections(new Localization(), [
            ['childLocalizations', new Localization()],
            ['titles', new LocalizedFallbackValue()],
        ]);
    }

    public function testLanguageCode(): void
    {
        $localization = new Localization();
        self::assertNull($localization->getLanguageCode());

        $localization->setLanguage((new Language())->setCode('test'));
        self::assertEquals('test', $localization->getLanguageCode());
    }

    /**
     * @dataProvider isUpdatedAtSetDataProvider
     */
    public function testIsUpdatedAtSet(bool $expected, \DateTime $date = null): void
    {
        $entity = new Localization();
        $entity->setUpdatedAt($date);

        self::assertEquals($expected, $entity->isUpdatedAtSet());
    }

    public function isUpdatedAtSetDataProvider(): array
    {
        return [
            ['expected' => true, 'date' => new \DateTime()],
            ['expected' => false, 'date' => null],
        ];
    }

    public function testToString(): void
    {
        $entity = new Localization();
        $expectedString = 'Expected String';
        $entity->setName($expectedString);
        self::assertEquals($expectedString, (string)$entity);
    }

    public function testConstruct(): void
    {
        $entity = new Localization();
        self::assertInstanceOf(Collection::class, $entity->getChildLocalizations());
        self::assertEmpty($entity->getChildLocalizations()->toArray());
        self::assertInstanceOf(Collection::class, $entity->getTitles());
        self::assertEmpty($entity->getTitles()->toArray());
    }

    public function testTitleAccessors(): void
    {
        $entity = $this->getLocalization(1);
        self::assertEmpty($entity->getTitles()->toArray());

        $defaultTitle = $this->createLocalizedValue('default', true);
        $firstTitle = $this->createLocalizedValue('test1');
        $secondTitleLocalization = $this->getLocalization(2);
        $secondTitle = $this->createLocalizedValue('test2', false, $secondTitleLocalization);

        $parentLocalization = $this->getLocalization(3);

        $localization = $this->getLocalization(4);
        $localization->setParentLocalization($parentLocalization);
        $withParentTitle = $this->createLocalizedValue('testParent', false, $parentLocalization);

        $entity->addTitle($defaultTitle)
            ->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle)
            ->addTitle($withParentTitle);

        self::assertCount(4, $entity->getTitles()->toArray());
        self::assertEquals(
            [$defaultTitle, $firstTitle, $secondTitle, $withParentTitle],
            array_values($entity->getTitles()->toArray())
        );

        self::assertEquals($secondTitle, $entity->getTitle($secondTitle->getLocalization()));
        self::assertEquals($defaultTitle, $entity->getTitle());
        self::assertEquals($withParentTitle, $entity->getTitle($localization));
        self::assertEquals($defaultTitle->getString(), $entity->getTitle(new Localization()));

        $entity->removeTitle($firstTitle)->removeTitle($firstTitle)->removeTitle($defaultTitle);

        self::assertEquals([$secondTitle, $withParentTitle], array_values($entity->getTitles()->toArray()));
    }

    public function testGetDefaultTitle(): void
    {
        $defaultTitle = $this->createLocalizedValue('default', true);
        $localizedTitle = $this->createLocalizedValue('default');

        $entity = new Localization();
        $entity->addTitle($defaultTitle)
            ->addTitle($localizedTitle);

        self::assertEquals($defaultTitle, $entity->getDefaultTitle());
    }

    public function testGetDefaultTitleWithDuplication(): void
    {
        $entity = new Localization();
        $firstFallbackValue = $this->createLocalizedValue('test1', true, null, 'test1');
        $duplicatedFallbackValue = $this->createLocalizedValue('test2', true, null, 'test2');
        foreach ([$firstFallbackValue, $duplicatedFallbackValue] as $title) {
            $entity->addTitle($title);
        }

        self::assertEquals($firstFallbackValue, $entity->getDefaultTitle());
    }

    public function testSetDefaultTitle(): void
    {
        $entity = new Localization();
        $entity->setDefaultTitle('test_title_string1');
        self::assertEquals('test_title_string1', $entity->getDefaultTitle());

        // check second time to make sure we don't have an exception in getter
        $entity->setDefaultTitle('test_title_string2');
        self::assertEquals('test_title_string2', $entity->getDefaultTitle());
    }

    protected function createLocalizedValue(
        string $value,
        bool $default = false,
        Localization $localization = null,
        string $fallbackValue = 'some string'
    ): LocalizedFallbackValue {
        $localized = (new LocalizedFallbackValue())->setString($fallbackValue);

        if (!$default) {
            if (!$localization) {
                $localization = new Localization();
            }
            $localization->setDefaultTitle($value);

            $localized->setLocalization($localization);
        }

        return $localized;
    }

    public function testGetHierarchy(): void
    {
        $parentLocalizationId = 42;
        $parentLocalization = $this->getLocalization($parentLocalizationId);
        $localization = new Localization();
        $localization->setParentLocalization($parentLocalization);

        self::assertEquals([$parentLocalizationId, null], $localization->getHierarchy());
    }

    /**
     * @dataProvider getChildrenIdsDataProvider
     */
    public function testGetChildrenIds(Localization $localization, bool $withOwnId, array $expected): void
    {
        self::assertEquals($expected, $localization->getChildrenIds($withOwnId));
    }

    public function getChildrenIdsDataProvider(): array
    {
        $localization = $this->getLocalization(42);
        $childLocalization1 = $this->getLocalization(105);
        $childLocalization1->addChildLocalization($this->getLocalization(110));
        $localization->addChildLocalization($childLocalization1);
        $localization->addChildLocalization($this->getLocalization(120));

        return [
            'empty localization without own id' => [
                'localization' => new Localization(),
                'withOwnId' => false,
                'expected' => []
            ],
            'empty localization with own id' => [
                'localization' => new Localization(),
                'withOwnId' => true,
                'expected' => []
            ],
            'localization without own id' => [
                'localization' => $localization,
                'withOwnId' => false,
                'expected' => [105, 110, 120]
            ],
            'localization with own id' => [
                'localization' => $localization,
                'withOwnId' => true,
                'expected' => [42, 105, 110, 120]
            ]
        ];
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }
}

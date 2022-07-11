<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\CustomLocalizedFallbackValueStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class LocalizedFallbackValueCollectionTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
    }

    public function testConstructWithInvalidValueClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value class must extend AbstractLocalizedFallbackValue');

        new LocalizedFallbackValueCollectionTransformer(
            $this->doctrine,
            'string',
            \DateTime::class
        );
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(string|array $field, array|ArrayCollection|null $source, ?array $expected)
    {
        $transformer = new LocalizedFallbackValueCollectionTransformer(
            $this->doctrine,
            $field,
            LocalizedFallbackValue::class
        );
        $this->assertEquals($expected, $transformer->transform($source));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function transformDataProvider(): array
    {
        $emptyStringValue = $this->createLocalizedFallbackValue(1, null, null, 'empty');
        $firstStringValue = $this->createLocalizedFallbackValue(2, 1, null, 'first');
        $secondStringValue = $this->createLocalizedFallbackValue(3, 2, null, 'second');
        $thirdStringValue = $this->createLocalizedFallbackValue(4, 3, FallbackType::SYSTEM);
        $defaultValueWithFallback = $this->createLocalizedFallbackValue(5, null, FallbackType::SYSTEM, 'default');
        $emptyIdValue = $this->createLocalizedFallbackValue(null, 4, FallbackType::SYSTEM);

        $emptyTextValue = $this->createLocalizedFallbackValue(5, null, null, null, 'empty');
        $firstTextValue = $this->createLocalizedFallbackValue(6, 1, null, null, 'first');
        $secondTextValue = $this->createLocalizedFallbackValue(7, 2, null, null, 'second');
        $thirdTextValue = $this->createLocalizedFallbackValue(8, 3, FallbackType::PARENT_LOCALIZATION);

        $emptyValues = $this->createLocalizedFallbackValue(1);
        $string1Value = $this->createLocalizedFallbackValue(2, 1, null, 'string1');
        $string2Value = $this->createLocalizedFallbackValue(3, 2, null, 'string2');
        $text1Value = $this->createLocalizedFallbackValue(4, 3, null, null, 'text1');
        $text2Value = $this->createLocalizedFallbackValue(5, 4, null, null, 'text2');
        $systemFallbackValue = $this->createLocalizedFallbackValue(6, 5, FallbackType::SYSTEM);
        $parentFallbackValue = $this->createLocalizedFallbackValue(7, 6, FallbackType::PARENT_LOCALIZATION);
        $bothValues = $this->createLocalizedFallbackValue(8, 7, null, 'string3', 'text3');
        $emptyIdValues = $this->createLocalizedFallbackValue(null, 8, FallbackType::SYSTEM);

        return [
            'null' => [
                'field' => 'string',
                'source' => null,
                'expected' => null,
            ],
            'default value with fallback' => [
                'field' => 'string',
                'source' => [$defaultValueWithFallback],
                'expected' => [
                    LocalizedFallbackValueCollectionType::FIELD_VALUES => [
                        '' => 'default',
                    ],
                    LocalizedFallbackValueCollectionType::FIELD_IDS => [
                        0 => 5,
                    ],
                ],
            ],
            'array of strings' => [
                'field' => 'string',
                'source' => [$emptyStringValue, $firstStringValue, $secondStringValue, $thirdStringValue],
                'expected' => [
                    LocalizedFallbackValueCollectionType::FIELD_VALUES => [
                        null => 'empty',
                        1 => 'first',
                        2 => 'second',
                        3 => new FallbackType(FallbackType::SYSTEM),
                    ],
                    LocalizedFallbackValueCollectionType::FIELD_IDS => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                    ],
                ],
            ],
            'empty id' => [
                'field' => 'string',
                'source' => [$emptyStringValue, $emptyIdValue],
                'expected' => [
                    LocalizedFallbackValueCollectionType::FIELD_VALUES => [
                        null => 'empty',
                        4 => new FallbackType(FallbackType::SYSTEM),
                    ],
                    LocalizedFallbackValueCollectionType::FIELD_IDS => [
                        0 => 1,
                    ],
                ],
            ],
            'collection of texts' => [
                'field' => 'text',
                'source' => new ArrayCollection(
                    [$emptyTextValue, $firstTextValue, $secondTextValue, $thirdTextValue]
                ),
                'expected' => [
                    LocalizedFallbackValueCollectionType::FIELD_VALUES => [
                        null => 'empty',
                        1 => 'first',
                        2 => 'second',
                        3 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                    ],
                    LocalizedFallbackValueCollectionType::FIELD_IDS => [
                        0 => 5,
                        1 => 6,
                        2 => 7,
                        3 => 8,
                    ],
                ],
            ],
            'multi fields' => [
                'field' => ['string', 'text'],
                'source' => new ArrayCollection(
                    [
                        $emptyValues,
                        $string1Value,
                        $string2Value,
                        $text1Value,
                        $text2Value,
                        $systemFallbackValue ,
                        $parentFallbackValue,
                        $bothValues,
                        $emptyIdValues,
                    ]
                ),
                'expected' => [
                    LocalizedFallbackValueCollectionType::FIELD_VALUES => [
                        null => [
                            'string' => null,
                            'text' => null
                        ],
                        1 => [
                            'string' => 'string1',
                            'text' => null
                        ],
                        2 => [
                            'string' => 'string2',
                            'text' => null
                        ],
                        3 => [
                            'string' => null,
                            'text' => 'text1'
                        ],
                        4 => [
                            'string' => null,
                            'text' => 'text2'
                        ],
                        5 => new FallbackType(FallbackType::SYSTEM),
                        6 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                        7 => [
                            'string' => 'string3',
                            'text' => 'text3'
                        ],
                        8 => new FallbackType(FallbackType::SYSTEM),
                    ],
                    LocalizedFallbackValueCollectionType::FIELD_IDS => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                        4 => 5,
                        5 => 6,
                        6 => 7,
                        7 => 8,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(
        array $values,
        array $localizations,
        string|array $field,
        ?array $source,
        ?ArrayCollection $expected
    ) {
        $valueClass = CustomLocalizedFallbackValueStub::class;
        $transformer = new LocalizedFallbackValueCollectionTransformer($this->doctrine, $field, $valueClass);
        $this->addDoctrineExpectations($values, $localizations, $valueClass);
        $this->assertEquals($expected, $transformer->reverseTransform($source));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            'null' => [
                'values' => [],
                'localizations' => [],
                'field' => 'string',
                'source' => null,
                'expected' => null,
            ],
            'valid data' => [
                'values' => [
                    1 => $this->createCustomLocalizedFallbackValue(1, null, 'default'),
                    2 => $this->createCustomLocalizedFallbackValue(2, 1, null, 'first'),
                    3 => $this->createCustomLocalizedFallbackValue(3, 2, FallbackType::SYSTEM),
                ],
                'localizations' => [
                    1 => $this->createLocalization(1),
                    2 => $this->createLocalization(2),
                    3 => $this->createLocalization(3),
                ],
                'field' => 'string',
                'source' => [
                    LocalizedFallbackValueCollectionType::FIELD_VALUES => [
                        null => 'default_updated',
                        1 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                        2 => 'second_updated',
                        3 => 'new_value',
                    ],
                    LocalizedFallbackValueCollectionType::FIELD_IDS => [
                        0 => 1,
                        1 => 2,
                        2 => 3
                    ],
                ],
                'expected' => new ArrayCollection([
                    $this->createCustomLocalizedFallbackValue(1, null, null, 'default_updated'),
                    $this->createCustomLocalizedFallbackValue(2, 1, FallbackType::PARENT_LOCALIZATION),
                    $this->createCustomLocalizedFallbackValue(3, 2, null, 'second_updated'),
                    $this->createCustomLocalizedFallbackValue(null, 3, null, 'new_value'),
                ]),
            ],
            'valid data multi field' => [
                'values' => [
                    1 => $this->createLocalizedFallbackValue(1, null, 'default'),
                    2 => $this->createLocalizedFallbackValue(2, 1, null, 'string1'),
                    3 => $this->createLocalizedFallbackValue(3, 2, null, null, 'text2'),
                    4 => $this->createLocalizedFallbackValue(4, 3, null, 'string3', 'text4'),
                    5 => $this->createLocalizedFallbackValue(5, 4, FallbackType::SYSTEM),
                    6 => $this->createLocalizedFallbackValue(6, 5, FallbackType::PARENT_LOCALIZATION),
                ],
                'localizations' => [
                    1 => $this->createLocalization(1),
                    2 => $this->createLocalization(2),
                    3 => $this->createLocalization(3),
                    4 => $this->createLocalization(4),
                    5 => $this->createLocalization(5),
                    6 => $this->createLocalization(6),
                ],
                'field' => ['string', 'text'],
                'source' => [
                    LocalizedFallbackValueCollectionType::FIELD_VALUES => [
                        null => ['string' => null, 'text' => 'default_updated'],
                        1 => ['string' => 'string1_updated', 'text' => null],
                        2 => ['string' => null, 'text' => 'text2_updated'],
                        3 => new FallbackType(FallbackType::SYSTEM),
                        4 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                        5 => ['string' => 'string4', 'text' => 'text4'],
                        6 => ['string' => 'new_string', 'text' => 'new_text'],
                    ],
                    LocalizedFallbackValueCollectionType::FIELD_IDS => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                        4 => 5,
                        5 => 6,
                    ],
                ],
                'expected' => new ArrayCollection([
                    $this->createLocalizedFallbackValue(1, null, null, null, 'default_updated'),
                    $this->createLocalizedFallbackValue(2, 1, null, 'string1_updated'),
                    $this->createLocalizedFallbackValue(3, 2, null, null, 'text2_updated'),
                    $this->createLocalizedFallbackValue(4, 3, FallbackType::SYSTEM),
                    $this->createLocalizedFallbackValue(5, 4, FallbackType::PARENT_LOCALIZATION),
                    $this->createLocalizedFallbackValue(6, 5, null, 'string4', 'text4'),
                    $this->createCustomLocalizedFallbackValue(null, 6, null, 'new_string', 'new_text'),
                ]),
            ],
        ];
    }

    public function testTransformUnexpectedType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array or Traversable", "DateTime" given');

        $transformer = new LocalizedFallbackValueCollectionTransformer(
            $this->doctrine,
            'text',
            LocalizedFallbackValue::class
        );
        $transformer->transform(new \DateTime());
    }

    public function testTransformUnexpectedTypeMultiField()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array or Traversable", "DateTime" given');

        $transformer = new LocalizedFallbackValueCollectionTransformer(
            $this->doctrine,
            ['text'],
            LocalizedFallbackValue::class
        );
        $transformer->transform(new \DateTime());
    }

    public function testReverseTransformUnexpectedType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "DateTime" given');

        $transformer = new LocalizedFallbackValueCollectionTransformer(
            $this->doctrine,
            'text',
            LocalizedFallbackValue::class
        );
        $transformer->reverseTransform(new \DateTime());
    }

    public function testReverseTransformUnexpectedTypeMultiField()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "DateTime" given');

        $transformer = new LocalizedFallbackValueCollectionTransformer(
            $this->doctrine,
            ['text'],
            LocalizedFallbackValue::class
        );
        $transformer->reverseTransform(new \DateTime());
    }

    public function testReverseTransformNoLocalization()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Undefined localization with ID=1');

        $transformer = new LocalizedFallbackValueCollectionTransformer(
            $this->doctrine,
            'text',
            LocalizedFallbackValue::class
        );
        $this->addDoctrineExpectations([], []);
        $transformer->reverseTransform(
            [LocalizedFallbackValueCollectionType::FIELD_VALUES => [1 => 'value']]
        );
    }

    private function addDoctrineExpectations(
        array $values,
        array $localizations,
        string $valueClass = LocalizedFallbackValue::class
    ) {
        $valueRepository = $this->createMock(ObjectRepository::class);
        $valueRepository->expects($this->any())
            ->method('find')
            ->willReturnMap($this->convertArrayToMap($values));

        $localizationRepository = $this->createMock(ObjectRepository::class);
        $localizationRepository->expects($this->any())
            ->method('find')
            ->willReturnMap($this->convertArrayToMap($localizations));

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [$valueClass, null, $valueRepository],
                [Localization::class, null, $localizationRepository],
            ]);
    }

    private function convertArrayToMap(array $source): array
    {
        $result = [];
        foreach ($source as $key => $value) {
            $result[] = [$key, $value];
        }

        return $result;
    }

    private function createLocalizedFallbackValue(
        ?int $id,
        int $localizationId = null,
        string $fallback = null,
        string $string = null,
        string $text = null
    ): LocalizedFallbackValue {
        $value = new LocalizedFallbackValue();
        $this->setLocalizedFallbackValues($value, $id, $localizationId, $fallback, $string, $text);

        return $value;
    }

    private function createCustomLocalizedFallbackValue(
        ?int $id,
        int $localizationId = null,
        string $fallback = null,
        string $string = null,
        string $text = null
    ): CustomLocalizedFallbackValueStub {
        $value = new CustomLocalizedFallbackValueStub();
        $this->setLocalizedFallbackValues($value, $id, $localizationId, $fallback, $string, $text);

        return $value;
    }

    private function setLocalizedFallbackValues(
        AbstractLocalizedFallbackValue $value,
        ?int $id,
        ?int $localizationId,
        ?string $fallback,
        ?string $string,
        ?string $text
    ) {
        if ($id) {
            ReflectionUtil::setId($value, $id);
        }

        $value->setFallback($fallback)
            ->setString($string)
            ->setText($text);

        if ($localizationId) {
            $value->setLocalization($this->createLocalization($localizationId));
        }
    }

    private function createLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }
}

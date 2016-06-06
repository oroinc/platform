<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class LocalizedFallbackValueCollectionTransformerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
    }

    /**
     * @param string $field
     * @param mixed $source
     * @param mixed $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($field, $source, $expected)
    {
        $transformer = new LocalizedFallbackValueCollectionTransformer($this->registry, $field);
        $this->assertEquals($expected, $transformer->transform($source));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        $emptyStringValue = $this->createLocalizedFallbackValue(1, null, null, 'empty');
        $firstStringValue = $this->createLocalizedFallbackValue(2, 1, null, 'first');
        $secondStringValue = $this->createLocalizedFallbackValue(3, 2, null, 'second');
        $thirdStringValue = $this->createLocalizedFallbackValue(4, 3, FallbackType::SYSTEM);

        $emptyTextValue = $this->createLocalizedFallbackValue(5, null, null, null, 'empty');
        $firstTextValue = $this->createLocalizedFallbackValue(6, 1, null, null, 'first');
        $secondTextValue = $this->createLocalizedFallbackValue(7, 2, null, null, 'second');
        $thirdTextValue = $this->createLocalizedFallbackValue(8, 3, FallbackType::PARENT_LOCALIZATION);

        return [
            'null' => [
                'field' => 'string',
                'source' => null,
                'expected' => null,
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
        ];
    }

    /**
     * @param array $values
     * @param array $localizations
     * @param string $field
     * @param mixed $source
     * @param mixed $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(array $values, array $localizations, $field, $source, $expected)
    {
        $transformer = new LocalizedFallbackValueCollectionTransformer($this->registry, $field);
        $this->addRegistryExpectations($values, $localizations);
        $this->assertEquals($expected, $transformer->reverseTransform($source));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
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
                    1 => $this->createLocalizedFallbackValue(1, null, 'default'),
                    2 => $this->createLocalizedFallbackValue(2, 1, null, 'first'),
                    3 => $this->createLocalizedFallbackValue(3, 2, FallbackType::SYSTEM),
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
                    $this->createLocalizedFallbackValue(1, null, null, 'default_updated'),
                    $this->createLocalizedFallbackValue(2, 1, FallbackType::PARENT_LOCALIZATION),
                    $this->createLocalizedFallbackValue(3, 2, null, 'second_updated'),
                    $this->createLocalizedFallbackValue(null, 3, null, 'new_value'),
                ]),
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array or Traversable", "DateTime" given
     */
    public function testTransformUnexpectedType()
    {
        $transformer = new LocalizedFallbackValueCollectionTransformer($this->registry, 'text');
        $transformer->transform(new \DateTime());
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "DateTime" given
     */
    public function testReverseTransformUnexpectedType()
    {
        $transformer = new LocalizedFallbackValueCollectionTransformer($this->registry, 'text');
        $transformer->reverseTransform(new \DateTime());
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Undefined localization with ID=1
     */
    public function testReverseTransformNoLocalization()
    {
        $transformer = new LocalizedFallbackValueCollectionTransformer($this->registry, 'text');
        $this->addRegistryExpectations([], []);
        $transformer->reverseTransform(
            [LocalizedFallbackValueCollectionType::FIELD_VALUES => [1 => 'value']]
        );
    }

    /**
     * @param array $values
     * @param array $localizations
     */
    protected function addRegistryExpectations(array $values, array $localizations)
    {
        $valueRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $valueRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap($this->convertArrayToMap($values)));

        $localizationRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $localizationRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap($this->convertArrayToMap($localizations)));

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap([
                    ['OroLocaleBundle:LocalizedFallbackValue', null, $valueRepository],
                    ['OroLocaleBundle:Localization', null, $localizationRepository],
                ])
            );
    }

    /**
     * @param array $source
     * @return array
     */
    protected function convertArrayToMap(array $source)
    {
        $result = [];
        foreach ($source as $key => $value) {
            $result[] = [$key, $value];
        }
        return $result;
    }

    /**
     * @param int $id
     * @param int|null $localizationId
     * @param string|null $fallback
     * @param string|null $string
     * @param string|null $text
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedFallbackValue(
        $id,
        $localizationId = null,
        $fallback = null,
        $string = null,
        $text = null
    ) {
        $value = new LocalizedFallbackValue();

        if ($id) {
            $reflection = new \ReflectionProperty(get_class($value), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($value, $id);
        }

        $value->setFallback($fallback)
            ->setString($string)
            ->setText($text);

        if ($localizationId) {
            $value->setLocalization($this->createLocalization($localizationId));
        }

        return $value;
    }

    /**
     * @param int $id
     * @return Localization
     */
    protected function createLocalization($id)
    {
        return $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', ['id' => $id]);
    }
}

<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\Serializer\Serializer;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Serializer\LocalizationArraySerializer;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizationArraySerializerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var Serializer */
    protected $serializer;

    public function setUp()
    {
        $this->serializer = new LocalizationArraySerializer();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnlyArrayFormatAvailableForSerialization()
    {
        $this->serializer->serialize('string', 'not_array');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnlyLocalizationCanBePassedToSerializer()
    {
        $this->serializer->serialize(new \stdClass(), 'array');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnlyArrayFormatCanBePassedToDeserializer()
    {
        $this->serializer->deserialize([], Localization::class, 'not_array');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnlyLocalizationTypeCanBePassedToDeserializer()
    {
        $this->serializer->deserialize([], 'not_localization', 'json');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnlyArrayDataCanBePassedToDeserializer()
    {
        $this->serializer->deserialize('{}', 'not_localization', 'json');
    }

    public function testEmptyLocalizationSerialization()
    {
        $localization = new Localization();
        $localization->setCreatedAt(new \DateTime('@1486059637'));
        $localization->setUpdatedAt(new \DateTime('@1486059637'));
        $localizationSerialized = $this->serializer->serialize($localization, 'array');

        $this->assertEquals(
            [
                'id' => null,
                'name' => null,
                'parentLocalization' => null,
                'formattingCode' => null,
                'languageCode' => null,
                'createdAt' => new \DateTime('@1486059637'),
                'updatedAt' => new \DateTime('@1486059637'),
                'updatedAtSet' => true,
                'titles' => []
            ],
            $localizationSerialized
        );
    }

    public function testItLoadsPersistentCollection()
    {
        $em = $this->getMockBuilder(EntityManagerInterface::class)
            ->getMock();

        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        $persistentCollection = new PersistentCollection($em, LocalizedFallbackValue::class, new ArrayCollection());
        $persistentCollection->setInitialized(false);
        $persistentCollection->setOwner(Localization::class, ['inversedBy' => 'localization']);

        $unitOfWork
            ->expects($this->once())
            ->method('loadCollection')
            ->with($persistentCollection);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $localizationMock = $this->getMockBuilder(Localization::class)->getMock();
        $localizationMock->method('getTitles')
            ->willReturn($persistentCollection);

        $this->serializer->serialize($localizationMock, 'array');

        $this->assertTrue($persistentCollection->isInitialized());
    }

    public function testCircularReferenceSerialization()
    {
        $localization = new Localization();
        $localization->setCreatedAt(new \DateTime('@1486059637'));
        $localization->setUpdatedAt(new \DateTime('@1486059637'));

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setLocalization($localization);

        $localization->addTitle($localizedFallbackValue);

        $localizationSerialized = $this->serializer->serialize($localization, 'array');

        $this->assertEquals([
            'id' => null,
            'name' => null,
            'parentLocalization' => null,
            'formattingCode' => null,
            'languageCode' => null,
            'createdAt' => new \DateTime('@1486059637'),
            'updatedAt' => new \DateTime('@1486059637'),
            'updatedAtSet' => true,
            'titles' => [
                [
                    'id' => null,
                    'text' => null,
                    'string' => null,
                    'fallback' => null
                ]
            ]
        ], $localizationSerialized);
    }

    public function testSerializationWithData()
    {
        $localization = new Localization();
        $localization->setName('test_name');
        $localization->setFormattingCode('en');
        $localization->setLanguageCode('EN');
        $localization->setCreatedAt(new \DateTime('@1486059637'));
        $localization->setUpdatedAt(new \DateTime('@1486059637'));

        $localizedFallbackValue1 = new LocalizedFallbackValue();
        $localizedFallbackValue1->setText('test_text1');
        $localizedFallbackValue1->setString('test_string1');
        $localizedFallbackValue1->setFallback('test_fallback1');

        $localizedFallbackValue2 = new LocalizedFallbackValue();
        $localizedFallbackValue2->setText('test_text2');
        $localizedFallbackValue2->setString('test_string2');
        $localizedFallbackValue2->setFallback('test_fallback2');

        $localization->addTitle($localizedFallbackValue1)
            ->addTitle($localizedFallbackValue2);

        $localizationSerialized = $this->serializer->serialize($localization, 'array');

        $this->assertEquals([
            'id' => null,
            'name' => 'test_name',
            'parentLocalization' => null,
            'formattingCode' => 'en',
            'languageCode' => 'EN',
            'createdAt' => new \DateTime('@1486059637'),
            'updatedAt' => new \DateTime('@1486059637'),
            'updatedAtSet' => true,
            'titles' =>
                [
                    [
                        'id' => null,
                        'text' => 'test_text1',
                        'string' => 'test_string1',
                        'fallback' => 'test_fallback1'
                    ],
                    [
                        'id' => null,
                        'text' => 'test_text2',
                        'string' => 'test_string2',
                        'fallback' => 'test_fallback2'
                    ]
                ]
        ], $localizationSerialized);
    }

    /**
     * @dataProvider deserializationProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testDeserializeEmptyClass(
        $array,
        $id,
        $languageCode,
        $formattingCode,
        $name,
        $parentLocalization,
        $titles,
        $hierarchy,
        $createdAt,
        $updatedAt,
        $updatedAtSet
    ) {
        /** @var Localization $localization */
        $localization = $this->serializer->deserialize($array, Localization::class, 'array');

        $this->assertEquals($id, $localization->getId());
        $this->assertEquals($languageCode, $localization->getLanguageCode());
        $this->assertEquals($name, $localization->getName());
        $this->assertEquals($parentLocalization, $localization->getParentLocalization());
        $this->assertEquals($formattingCode, $localization->getFormattingCode());
        $localizationTitles = $localization->getTitles();
        foreach ($titles as $i => $title) {
            $this->assertEquals($title['id'], $localizationTitles[$i]->getId());
            $this->assertEquals($title['fallback'], $localizationTitles[$i]->getFallback());
            $this->assertEquals($title['string'], $localizationTitles[$i]->getString());
            $this->assertEquals($title['text'], $localizationTitles[$i]->getText());
        }
        $this->assertEquals($hierarchy, $localization->getHierarchy());
        $this->assertEquals($createdAt, $localization->getCreatedAt());
        $this->assertEquals($updatedAt, $localization->getUpdatedAt());
        $this->assertEquals($updatedAtSet, $localization->isUpdatedAtSet());
    }

    /**
      * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function deserializationProvider()
    {
        return [
            'Empty object' => [
                'array' => [
                    'id' => null,
                    'languageCode' => null,
                    'formattingCode' => null,
                    'name' => null,
                    'parentLocalization' => null,
                    'titles' => [],
                    'hierarchy' => [null],
                    'createdAt' => null,
                    'updatedAt' => null,
                    'updatedAtSet' => null
                ],
                'id' => null,
                'languageCode' => null,
                'formattingCode' => null,
                'name' => null,
                'parentLocalization' => null,
                'titles' => [],
                'hierarchy' => [null],
                'createdAt' => null,
                'updatedAt' => null,
                'updatedAtSet' => null
            ],
            'Filled object' => [
                'array' => [
                    'id' => 1,
                    'languageCode' => 'en',
                    'formattingCode' => 'en',
                    'name' => 'test_name',
                    'parentLocalization' => null,
                    'titles' => [],
                    'hierarchy' => [null],
                    'createdAt' => new \DateTime('@1486059637'),
                    'updatedAt' => new \DateTime('@1486059637'),
                    'updatedAtSet' => null
                ],
                'id' => 1,
                'languageCode' => 'en',
                'formattingCode' => 'en',
                'name' => 'test_name',
                'parentLocalization' => null,
                'titles' => [],
                'hierarchy' => [null],
                'createdAt' => new \DateTime('@1486059637'),
                'updatedAt' => new \DateTime('@1486059637'),
                'updatedAtSet' => null
            ],
            'Filled object with titles' => [
                'array' => [
                    'id' => 2,
                    'languageCode' => 'EN',
                    'formattingCode' => 'en',
                    'name' => 'test_name',
                    'parentLocalization' => null,
                    'titles' => [
                        [
                            'id' => null,
                            'fallback' => 'test_fallback1',
                            'string' => 'test_string1',
                            'text' => 'test_text1',
                            'localization' => null
                        ],
                        [
                            'id' => null,
                            'fallback' => 'test_fallback2',
                            'string' => 'test_string2',
                            'text' => 'test_text2',
                            'localization' => null
                        ],
                    ],
                    'hierarchy' => [null],
                    'createdAt' => new \DateTime('@1486059637'),
                    'updatedAt' => new \DateTime('@1486059637'),
                    'updatedAtSet' => null
                ],
                'id' => 2,
                'languageCode' => 'EN',
                'formattingCode' => 'en',
                'name' => 'test_name',
                'parentLocalization' => null,
                'titles' => [
                    [
                        'id' => null,
                        'fallback' => 'test_fallback1',
                        'string' => 'test_string1',
                        'text' => 'test_text1',
                        'localization' => null
                    ],
                    [
                        'id' => null,
                        'fallback' => 'test_fallback2',
                        'string' => 'test_string2',
                        'text' => 'test_text2',
                        'localization' => null
                    ],
                ],
                'hierarchy' => [null],
                'createdAt' => new \DateTime('@1486059637'),
                'updatedAt' => new \DateTime('@1486059637'),
                'updatedAtSet' => null
            ],
            'With parentLocalization' => [
                'array' => [
                    'id' => 2,
                    'languageCode' => null,
                    'formattingCode' => null,
                    'name' => 'Child',
                    'parentLocalization' => 1,
                    'titles' => [],
                    'hierarchy' => [null],
                    'createdAt' => null,
                    'updatedAt' => null,
                    'updatedAtSet' => null
                ],
                'id' => 2,
                'languageCode' => null,
                'formattingCode' => null,
                'name' => 'Child',
                'parentLocalization' => $this->getEntity(Localization::class, ['id' => 1]),
                'titles' => [],
                'hierarchy' => [1, null],
                'createdAt' => null,
                'updatedAt' => null,
                'updatedAtSet' => null
            ],
            'Empty array' => [
                'array' => [],
                'id' => null,
                'languageCode' => null,
                'formattingCode' => null,
                'name' => null,
                'parentLocalization' => null,
                'titles' => [],
                'hierarchy' => [null],
                'createdAt' => null,
                'updatedAt' => null,
                'updatedAtSet' => null
            ],
        ];
    }
}

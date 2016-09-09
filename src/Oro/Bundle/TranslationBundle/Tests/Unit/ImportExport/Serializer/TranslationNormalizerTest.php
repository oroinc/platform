<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\ImportExport\Serializer\TranslationNormalizer;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class TranslationNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationManager;

    /** @var TranslationNormalizer */
    protected $normalizer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translationManager = $this->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new TranslationNormalizer($this->translationManager);
    }

    public function testDenormalize()
    {
        $language = (new Language())->setCode('test_code');

        $data = [
            'domain' => 'test_domain',
            'key' => 'test_key',
            'value' => 'test_value',
        ];
        $context = ['language_code' => 'test_code'];

        $this->translationManager->expects($this->once())
            ->method('getLanguageByCode')
            ->with('test_code')
            ->willReturn($language);

        $result = $this->normalizer->denormalize($data, Translation::class, null, $context);
        $translation = new Translation();
        $translation
            ->setLanguage($language)
            ->setDomain($data['domain'])
            ->setValue($data['value'])
            ->setKey($data['key']);

        $this->assertEquals($translation, $result);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException
     * @expectedExceptionMessage Incorrect record format
     */
    public function testDenormalizeEmpty()
    {
        $this->normalizer->denormalize([], Translation::class);
    }

    /**
     * @param string $type
     * @param string $languageCode
     * @param bool $expected
     *
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($type, $languageCode, $expected)
    {
        $context = ['language_code' => $languageCode];
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization([], $type, null, $context));
    }

    /**
     * @return array
     */
    public function supportsDenormalizationDataProvider()
    {
        return [
            'wrong class' => ['\stdClass', 'en_US', false],
            'empty class' => ['', 'en_US', false],
            'no language code' => [Translation::class, '', false],
            'right data' => [Translation::class, 'en_US', true],
        ];
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
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
        $translationKey = (new TranslationKey())->setDomain('test_domain')->setKey('test_key');
        $translation = new Translation();
        $translation
            ->setLanguage($language)
            ->setTranslationKey($translationKey)
            ->setValue('test_value');

        $data = [
            'domain' => 'test_domain',
            'key' => 'test_key',
            'value' => 'test_value',
        ];
        $context = ['language_code' => 'test_code'];

        $this->translationManager->expects($this->once())
            ->method('createValue')
            ->with('test_key', 'test_value', 'test_code', 'test_domain')
            ->willReturn($translation);

        $result = $this->normalizer->denormalize($data, Translation::class, null, $context);

        $this->assertSame($translation, $result);
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

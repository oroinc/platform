<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\ImportExport\Serializer\TranslationNormalizer;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class TranslationNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $translationManager;

    /** @var TranslationNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->translationManager = $this->createMock(TranslationManager::class);

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
            ->method('createTranslation')
            ->with('test_key', 'test_value', 'test_code', 'test_domain')
            ->willReturn($translation);

        $this->assertEquals(Translation::SCOPE_SYSTEM, $translation->getScope());

        $result = $this->normalizer->denormalize($data, Translation::class, null, $context);

        $this->assertSame($translation, $result);
        $this->assertEquals(Translation::SCOPE_UI, $translation->getScope());
    }

    public function testDenormalizeEmpty()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Incorrect record format');

        $this->normalizer->denormalize([], Translation::class);
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(string $type, string $languageCode, bool $expected)
    {
        $context = ['language_code' => $languageCode];
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization([], $type, null, $context));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            'wrong class' => [\stdClass::class, 'en_US', false],
            'empty class' => ['', 'en_US', false],
            'no language code' => [Translation::class, '', false],
            'right data' => [Translation::class, 'en_US', true],
        ];
    }
}

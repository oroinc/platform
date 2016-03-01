<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class ConfigTranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    const LOCALE = 'en';

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslationRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DynamicTranslationMetadataCache */
    protected $translationCache;

    /** @var ConfigTranslationHelper */
    protected $helper;

    protected function setUp()
    {
        $this->repository = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->translationCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new ConfigTranslationHelper($this->registry, $this->translator, $this->translationCache);
    }

    protected function tearDown()
    {
        unset($this->registry, $this->translator, $this->translationCache, $this->repository, $this->helper);
    }

    /**
     * @dataProvider isTranslationEqualDataProvider
     *
     * @param string $translation
     * @param string $key
     * @param string $value
     * @param bool $expected
     */
    public function testIsTranslationEqual($translation, $key, $value, $expected)
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($key)
            ->willReturn($translation);

        $this->assertEquals($expected, $this->helper->isTranslationEqual($key, $value));
    }

    /**
     * @return array
     */
    public function isTranslationEqualDataProvider()
    {
        return [
            'equal' => [
                'translation' => 'valid translation',
                'key' => 'test',
                'value' => 'valid translation',
                'expected' => true
            ],
            'not equal' => [
                'translation' => 'valid translation',
                'key' => 'test',
                'value' => 'invalid value',
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider saveTranslationsDataProvider
     *
     * @param array $translations
     * @param string|null $key
     * @param string|null $value
     */
    public function testSaveTranslations(array $translations, $key = null, $value = null)
    {
        if ($translations) {
            $this->assertTranslationRepositoryCalled($key, $value);
            $this->assertTranslationServicesCalled();
        } else {
            $this->repository->expects($this->never())->method($this->anything());
            $this->translationCache->expects($this->never())->method($this->anything());
        }

        $this->helper->saveTranslations($translations);
    }

    /**
     * @return array
     */
    public function saveTranslationsDataProvider()
    {
        $key = 'test.domain.label';
        $value = 'translation label';

        return [
            [
                'translations' => []
            ],
            [
                'translations' => [$key => $value],
                'key' => $key,
                'value' => $value
            ],
        ];
    }

    /**
     * @param string $key
     * @param string $value
     */
    protected function assertTranslationRepositoryCalled($key, $value)
    {
        $trans = new Translation();

        $this->repository->expects($this->once())
            ->method('saveValue')
            ->with($key, $value, self::LOCALE, TranslationRepository::DEFAULT_DOMAIN, Translation::SCOPE_UI)
            ->willReturn($trans);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Translation::ENTITY_NAME)
            ->willReturn($this->repository);
        $manager->expects($this->once())
            ->method('flush')
            ->with([$trans]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::ENTITY_NAME)
            ->willReturn($manager);
    }

    protected function assertTranslationServicesCalled()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $this->translationCache->expects($this->once())
            ->method('updateTimestamp')
            ->with(self::LOCALE);
    }
}

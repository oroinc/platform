<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Command\OroLanguageUpdateCommand;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Exception\TranslationProviderException;
use Oro\Bundle\TranslationBundle\Provider\ExternalTranslationsProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;

/**
 * @dbIsolationPerTest
 */
class OroLanguageUpdateCommandTest extends WebTestCase
{
    /** @var  ExternalTranslationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $externalTranslationsProvider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadLanguages::class]);
        $this->replaceExternalTranslationsProvider();
    }

    public function testExecuteWithoutOptions()
    {
        $this->externalTranslationsProvider->expects($this->any())->method('hasTranslations')
            ->willReturn(true);

        $result = $this->runCommand(OroLanguageUpdateCommand::getDefaultName());

        $this->assertNotEmpty($result);
        static::assertStringContainsString('Avail. Translations', $result);
        static::assertStringContainsString('N/A', $result);
        static::assertStringContainsString('English', $result);
        static::assertStringContainsString('en_CA', $result);
        static::assertStringContainsString('fr_FR', $result);
        static::assertStringContainsString('en_US', $result);
    }

    public function testExecuteWithWrongLanguageCodeOption()
    {
        $this->externalTranslationsProvider->expects($this->never())->method('updateTranslations');

        $result = $this->runCommand(OroLanguageUpdateCommand::getDefaultName(), ['--language' => 'WRONG_CODE']);
        $this->assertNotEmpty($result);

        $expectedMessages = [
            'Language "WRONG_CODE" not found',
        ];

        foreach ($expectedMessages as $message) {
            static::assertStringContainsString($message, $result);
        }
    }

    /**
     * @param $code
     * @param $hasTranslations
     * @param $expectedMessages
     *
     * @dataProvider languageDataProvider
     */
    public function testExecuteWithLanguageOption($code, $hasTranslations, $expectedMessages)
    {
        /** @var Language $language */
        $language = $this->getReference($code);

        $this->externalTranslationsProvider->expects($this->once())
            ->method('updateTranslations')
            ->with($language)
            ->willReturn($hasTranslations);

        $result = $this->runCommand(OroLanguageUpdateCommand::getDefaultName(), ['--language' => $language->getCode()]);
        $this->assertNotEmpty($result);

        foreach ($expectedMessages as $message) {
            static::assertStringContainsString(sprintf($message, $this->getLanguageName($language)), $result);
        }
    }

    public function testExecuteWithExceptions()
    {
        /** @var Language $language */
        $language = $this->getReference(LoadLanguages::LANGUAGE1);

        $this->externalTranslationsProvider->expects($this->once())
            ->method('updateTranslations')
            ->with($language)
            ->willThrowException(new TranslationProviderException('EXCEPTION_TEXT'));

        $result = $this->runCommand(OroLanguageUpdateCommand::getDefaultName(), ['--language' => $language->getCode()]);
        $this->assertNotEmpty($result);

        static::assertStringContainsString('EXCEPTION_TEXT', $result);
    }

    public function testWithAllOption()
    {
        $languages = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Language::class)
            ->findAll();

        $langCount = count($languages);

        $this->externalTranslationsProvider->expects($this->exactly($langCount))
            ->method('updateTranslations')
            ->willReturn(true);

        $result = $this->runCommand(OroLanguageUpdateCommand::getDefaultName(), ['--all' => true]);

        foreach ($languages as $language) {
            static::assertStringContainsString(
                sprintf('Processing language "%s" ...', $this->getLanguageName($language)),
                $result
            );
            static::assertStringContainsString(
                sprintf('Installation completed for "%s"', $this->getLanguageName($language)),
                $result
            );
        }
    }

    /**
     * @return \Generator
     */
    public function languageDataProvider()
    {
        yield 'with translations' => [
            'code' => 'fr_FR',
            'hasTranslations' => true,
            'expectedMessages' => [
                'Processing language "%s" ...',
                'Installation completed for "%s"',
            ],
        ];
        yield 'without translations' => [
            'code' => 'en_CA',
            'hasTranslations' => false,
            'expectedMessages' => [
                'No available translations found for "%s"',
            ],
        ];
    }

    /**
     * @param Language $language
     *
     * @return null|string
     */
    private function getLanguageName(Language $language)
    {
        $name = Languages::getName($language->getCode(), 'en');
        if ($name) {
            return $name;
        }

        $name = Locales::getName($language->getCode(), 'en');
        if ($name) {
            return $name;
        }

        return $name;
    }

    private function replaceExternalTranslationsProvider()
    {
        $this->externalTranslationsProvider = $this->createMock(ExternalTranslationsProvider::class);
        $this->getContainer()
            ->set('oro_translation.provider.external_translations', $this->externalTranslationsProvider);
    }
}

<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Action\IsLanguageTranslationUpdateAvailableCondition;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class IsLanguageTranslationUpdateAvailableConditionTest extends \PHPUnit\Framework\TestCase
{
    private string $codeWithout;
    private string $codeNew;
    private string $codeOld;
    private Language $languageWithoutTranslations;
    private Language $languageWithNewTranslations;
    private Language $languageWithOldTranslations;
    private IsLanguageTranslationUpdateAvailableCondition $condition1;
    private IsLanguageTranslationUpdateAvailableCondition $condition2;
    private IsLanguageTranslationUpdateAvailableCondition $condition3;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $tomorrow = new \DateTime('tomorrow');
        $now = new \DateTime();
        $yesterday = new \DateTime('yesterday');

        $this->codeWithout = 'fr_FR';
        $this->codeNew = 'de_DE';
        $this->codeOld = 'es_ES';

        $this->languageWithoutTranslations = (new Language())->setCode($this->codeWithout)->setInstalledBuildDate($now);
        $this->languageWithNewTranslations = (new Language())->setCode($this->codeNew)->setInstalledBuildDate($now);
        $this->languageWithOldTranslations = (new Language())->setCode($this->codeOld)->setInstalledBuildDate($now);

        $languageRepository = $this->createMock(LanguageRepository::class);
        $languageRepository->method('findOneBy')->willReturnMap([
            [['code' => $this->codeWithout], null, $this->languageWithoutTranslations],
            [['code' => $this->codeNew], null, $this->languageWithNewTranslations],
            [['code' => $this->codeOld], null, $this->languageWithOldTranslations],
        ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturnMap([
            [Language::class, null, $languageRepository]
        ]);

        $translationMetricsProvider = $this->createMock(TranslationMetricsProviderInterface::class);
        $translationMetricsProvider->method('getForLanguage')->willReturnMap([
            [$this->codeWithout, null],
            [$this->codeNew, ['code' => $this->codeNew, 'lastBuildDate' => $tomorrow, 'translationStatus' => 90]],
            [$this->codeOld, ['code' => $this->codeOld, 'lastBuildDate' => $yesterday, 'translationStatus' => 90]],
        ]);

        $this->condition1 = new IsLanguageTranslationUpdateAvailableCondition($translationMetricsProvider, $doctrine);
        $this->condition2 = new IsLanguageTranslationUpdateAvailableCondition($translationMetricsProvider, $doctrine);
        $this->condition3 = new IsLanguageTranslationUpdateAvailableCondition($translationMetricsProvider, $doctrine);

        $this->condition1->setContextAccessor(new ContextAccessor());
        $this->condition2->setContextAccessor(new ContextAccessor());
        $this->condition3->setContextAccessor(new ContextAccessor());
    }
    public function testWithScalarValue(): void
    {
        $context = [];

        static::assertFalse($this->condition1->initialize([$this->codeWithout])->evaluate($context));
        static::assertTrue($this->condition2->initialize([$this->codeNew])->evaluate($context));
        static::assertFalse($this->condition3->initialize([$this->codeOld])->evaluate($context));
    }

    public function testWithEntityValue(): void
    {
        $context = [];

        static::assertFalse($this->condition1->initialize([$this->languageWithoutTranslations])->evaluate($context));
        static::assertTrue($this->condition2->initialize([$this->languageWithNewTranslations])->evaluate($context));
        static::assertFalse($this->condition3->initialize([$this->languageWithOldTranslations])->evaluate($context));
    }

    public function testWithPropertyPathToScalarValue(): void
    {
        $context = ['$' => [
            'language1' => $this->codeWithout,
            'language2' => $this->codeNew,
            'language3' => $this->codeOld,
        ]];

        $this->condition1->initialize([new PropertyPath('$.language1')]);
        $this->condition2->initialize([new PropertyPath('$.language2')]);
        $this->condition3->initialize([new PropertyPath('$.language3')]);

        static::assertFalse($this->condition1->evaluate($context));
        static::assertTrue($this->condition2->evaluate($context));
        static::assertFalse($this->condition3->evaluate($context));
    }

    public function testWithPropertyPathToEntityValue(): void
    {
        $context = ['$' => [
            'language1' => $this->languageWithoutTranslations,
            'language2' => $this->languageWithNewTranslations,
            'language3' => $this->languageWithOldTranslations,
        ]];

        $this->condition1->initialize([new PropertyPath('$.language1')]);
        $this->condition2->initialize([new PropertyPath('$.language2')]);
        $this->condition3->initialize([new PropertyPath('$.language3')]);

        static::assertFalse($this->condition1->evaluate($context));
        static::assertTrue($this->condition2->evaluate($context));
        static::assertFalse($this->condition3->evaluate($context));
    }
}

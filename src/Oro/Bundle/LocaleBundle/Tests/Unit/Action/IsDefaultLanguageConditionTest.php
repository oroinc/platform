<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Action\IsDefaultLanguageCondition;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class IsDefaultLanguageConditionTest extends \PHPUnit\Framework\TestCase
{
    private string $otherCode;
    private string $defaultCode;
    private Language $otherLanguage;
    private Language $defaultLanguage;
    private IsDefaultLanguageCondition $condition1;
    private IsDefaultLanguageCondition $condition2;

    protected function setUp(): void
    {
        $this->otherCode = 'fr_FR';
        $this->defaultCode = 'de_DE';

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::LANGUAGE))
            ->willReturn($this->defaultCode);

        $this->otherLanguage = (new Language())->setCode($this->otherCode);
        $this->defaultLanguage = (new Language())->setCode($this->defaultCode);

        $languageRepository = $this->createMock(LanguageRepository::class);
        $languageRepository->expects(self::any())
            ->method('findOneBy')
            ->willReturnMap([
                [['code' => $this->otherCode], null, $this->otherLanguage],
                [['code' => $this->defaultCode], null, $this->defaultLanguage],
            ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($languageRepository);

        $this->condition1 = new IsDefaultLanguageCondition($configManager, $doctrine);
        $this->condition2 = new IsDefaultLanguageCondition($configManager, $doctrine);

        $this->condition1->setContextAccessor(new ContextAccessor());
        $this->condition2->setContextAccessor(new ContextAccessor());
    }

    public function testWithScalarValue(): void
    {
        $context = [];

        self::assertFalse($this->condition1->initialize([$this->otherCode])->evaluate($context));
        self::assertTrue($this->condition2->initialize([$this->defaultCode])->evaluate($context));
    }

    public function testWithEntityValue(): void
    {
        $context = [];

        self::assertFalse($this->condition1->initialize([$this->otherLanguage])->evaluate($context));
        self::assertTrue($this->condition2->initialize([$this->defaultLanguage])->evaluate($context));
    }

    public function testWithPropertyPathToScalarValue(): void
    {
        $context = ['$' => [
            'language1' => $this->otherCode,
            'language2' => $this->defaultCode,
        ]];

        $this->condition1->initialize([new PropertyPath('$.language1')]);
        $this->condition2->initialize([new PropertyPath('$.language2')]);

        self::assertFalse($this->condition1->evaluate($context));
        self::assertTrue($this->condition2->evaluate($context));
    }

    public function testWithPropertyPathToEntityValue(): void
    {
        $context = ['$' => [
            'language1' => $this->otherLanguage,
            'language2' => $this->defaultLanguage,
        ]];

        $this->condition1->initialize([new PropertyPath('$.language1')]);
        $this->condition2->initialize([new PropertyPath('$.language2')]);

        self::assertFalse($this->condition1->evaluate($context));
        self::assertTrue($this->condition2->evaluate($context));
    }
}

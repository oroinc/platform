<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Action;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Action\CheckIfDefaultLanguageAction;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class CheckIfDefaultLanguageActionTest extends \PHPUnit\Framework\TestCase
{
    private string $otherCode;
    private string $defaultCode;
    private Language $otherLanguage;
    private Language $defaultLanguage;
    private CheckIfDefaultLanguageAction $action1;
    private CheckIfDefaultLanguageAction $action2;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $configManager = $this->createMock(ConfigManager::class);

        $this->otherCode = 'fr_FR';
        $this->defaultCode = 'de_DE';

        $configManager->method('get')->willReturnMap([
            [Configuration::getConfigKeyByName(Configuration::LANGUAGE), false, false, null, $this->defaultCode]
        ]);

        $this->otherLanguage = (new Language())->setCode($this->otherCode);
        $this->defaultLanguage = (new Language())->setCode($this->defaultCode);

        $this->action1 = new CheckIfDefaultLanguageAction(new ContextAccessor(), $configManager);
        $this->action2 = new CheckIfDefaultLanguageAction(new ContextAccessor(), $configManager);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action1->setDispatcher($eventDispatcher);
        $this->action2->setDispatcher($eventDispatcher);
    }

    public function testWithLanguageCode(): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);

        $this->action1->initialize([
            'language' => $this->otherCode,
            'result' => new PropertyPath('$.result1')
        ]);
        $this->action1->execute($context);
        static::assertFalse($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->defaultCode,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        static::assertTrue($context['$']['result2']);
    }

    public function testWithLanguageEntity(): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);

        $this->action1->initialize([
            'language' => $this->otherLanguage,
            'result' => new PropertyPath('$.result1')
        ]);
        $this->action1->execute($context);
        static::assertFalse($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->defaultLanguage,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        static::assertTrue($context['$']['result2']);
    }
}

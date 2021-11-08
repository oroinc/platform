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
        self::assertFalse($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->defaultCode,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        self::assertTrue($context['$']['result2']);
    }

    public function testWithLanguageEntity(): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);

        $this->action1->initialize([
            'language' => $this->otherLanguage,
            'result' => new PropertyPath('$.result1')
        ]);
        $this->action1->execute($context);
        self::assertFalse($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->defaultLanguage,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        self::assertTrue($context['$']['result2']);
    }
}

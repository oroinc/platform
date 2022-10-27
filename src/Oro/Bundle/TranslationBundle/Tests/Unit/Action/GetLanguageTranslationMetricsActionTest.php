<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Action;

use Oro\Bundle\TranslationBundle\Action\GetLanguageTranslationMetricsAction;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetLanguageTranslationMetricsActionTest extends \PHPUnit\Framework\TestCase
{
    private string $codeWithoutTranslations;
    private string $codeWithTranslations;
    private Language $languageWithoutTranslations;
    private Language $languageWithTranslations;
    private GetLanguageTranslationMetricsAction $action1;
    private GetLanguageTranslationMetricsAction $action2;
    private array $metrics;

    protected function setUp(): void
    {
        $this->codeWithoutTranslations = 'fr_FR';
        $this->codeWithTranslations = 'de_DE';

        $this->metrics = [
            'code' => 'uk_UA',
            'translationStatus' => 30,
            'lastBuildDate' => new \DateTime()
        ];

        $translationMetricsProvider = $this->createMock(TranslationMetricsProviderInterface::class);
        $translationMetricsProvider->expects(self::any())
            ->method('getForLanguage')
            ->willReturnMap([
                [$this->codeWithoutTranslations, null],
                [$this->codeWithTranslations, $this->metrics],
            ]);

        $this->action1 = new GetLanguageTranslationMetricsAction(new ContextAccessor(), $translationMetricsProvider);
        $this->action2 = new GetLanguageTranslationMetricsAction(new ContextAccessor(), $translationMetricsProvider);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action1->setDispatcher($eventDispatcher);
        $this->action2->setDispatcher($eventDispatcher);

        $this->languageWithoutTranslations = (new Language())->setCode($this->codeWithoutTranslations);
        $this->languageWithTranslations = (new Language())->setCode($this->codeWithTranslations);
    }

    public function testWithLanguageCode(): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);

        $this->action1->initialize([
            'language' => $this->codeWithoutTranslations,
            'result' => new PropertyPath('$.result1')
        ]);
        $this->action1->execute($context);
        self::assertNull($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->codeWithTranslations,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        self::assertSame($this->metrics, $context['$']['result2']);
    }

    public function testWithLanguageEntity(): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);

        $this->action1->initialize([
            'language' => $this->languageWithoutTranslations,
            'result' => new PropertyPath('$.result1')
        ]);
        $this->action1->execute($context);
        self::assertNull($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->languageWithTranslations,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        self::assertSame($this->metrics, $context['$']['result2']);
    }
}

<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Action;

use Oro\Bundle\TranslationBundle\Action\DownloadUpdateLanguageTranslationAction;
use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Exception\TranslationDownloaderException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class DownloadUpdateLanguageTranslationActionTest extends \PHPUnit\Framework\TestCase
{
    private string $codeWithoutTranslations;
    private string $codeWithTranslations;
    private Language $languageWithoutTranslations;
    private Language $languageWithTranslations;
    private DownloadUpdateLanguageTranslationAction $action1;
    private DownloadUpdateLanguageTranslationAction $action2;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $this->codeWithoutTranslations = 'fr_FR';
        $this->codeWithTranslations = 'de_DE';

        $translationDownloader = $this->createMock(TranslationDownloader::class);
        $translationDownloader->method('downloadAndApplyTranslations')->willReturnCallback(
            function (string $languageCode) {
                if ($this->codeWithoutTranslations === $languageCode) {
                    throw new TranslationDownloaderException(
                        \sprintf('No available translations found for "%s".', $languageCode)
                    );
                }
            }
        );

        $this->action1 = new DownloadUpdateLanguageTranslationAction(new ContextAccessor(), $translationDownloader);
        $this->action2 = new DownloadUpdateLanguageTranslationAction(new ContextAccessor(), $translationDownloader);

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
        static::assertFalse($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->codeWithTranslations,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        static::assertTrue($context['$']['result2']);
    }

    public function testWithLanguageEntity(): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);

        $this->action1->initialize([
            'language' => $this->languageWithoutTranslations,
            'result' => new PropertyPath('$.result1')
        ]);
        $this->action1->execute($context);
        static::assertFalse($context['$']['result1']);

        $this->action2->initialize([
            'language' => $this->languageWithTranslations,
            'result' => new PropertyPath('$.result2')
        ]);
        $this->action2->execute($context);
        static::assertTrue($context['$']['result2']);
    }
}

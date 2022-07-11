<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Action;

use Oro\Bundle\TranslationBundle\Action\DownloadUpdateLanguageTranslationAction;
use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Exception\TranslationDownloaderException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class DownloadUpdateLanguageTranslationActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationDownloader|\PHPUnit\Framework\MockObject\MockObject */
    private $translationDownloader;

    /** @var BufferingLogger */
    private $logger;

    /** @var DownloadUpdateLanguageTranslationAction */
    private $action;

    protected function setUp(): void
    {
        $this->translationDownloader = $this->createMock(TranslationDownloader::class);
        $this->logger = new BufferingLogger();

        $this->action = new DownloadUpdateLanguageTranslationAction(
            new ContextAccessor(),
            $this->translationDownloader,
            $this->logger
        );
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    private function getLanguage(string $languageCode): Language
    {
        $language = new Language();
        $language->setCode($languageCode);

        return $language;
    }

    public function languageDataProvider(): array
    {
        return [
            ['en_US'],
            [$this->getLanguage('en_US')]
        ];
    }

    /**
     * @dataProvider languageDataProvider
     */
    public function testForLanguageWithTranslations(string|Language $language): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);

        $this->translationDownloader->expects(self::once())
            ->method('downloadAndApplyTranslations');

        $this->action->initialize(['language' => $language, 'result' => new PropertyPath('$.result2')]);
        $this->action->execute($context);
        self::assertTrue($context['$']['result2']);

        self::assertEquals([], $this->logger->cleanLogs());
    }

    /**
     * @dataProvider languageDataProvider
     */
    public function testForLanguageWithoutTranslations(string|Language $language): void
    {
        $context = new \ArrayObject(['$' => ['result1' => null, 'result2' => null]]);
        $exception = new TranslationDownloaderException('No available translations found for "en_US".');

        $this->translationDownloader->expects(self::any())
            ->method('downloadAndApplyTranslations')
            ->willThrowException($exception);

        $this->action->initialize(['language' => $language, 'result' => new PropertyPath('$.result1')]);
        $this->action->execute($context);
        self::assertFalse($context['$']['result1']);

        self::assertEquals(
            [['error', 'The download translations failed.', ['exception' => $exception]]],
            $this->logger->cleanLogs()
        );
    }
}

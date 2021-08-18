<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Controller\Controller;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class JsTranslationDumperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var Controller|\PHPUnit\Framework\MockObject\MockObject */
    private $translationControllerMock;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var JsTranslationDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->translationControllerMock = $this->createMock(Controller::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);

        $this->dumper = new JsTranslationDumper(
            $this->translationControllerMock,
            $this->router,
            [],
            '',
            $this->languageProvider
        );
        $this->dumper->setLogger($this->logger);
    }

    public function testDumpTranslations()
    {
        $routeMock = $this->createMock(Route::class);
        $routeMock->expects($this->once())
            ->method('getPath')
            ->willReturn($this->getTempDir('js_trans_dumper', null) . '/test{_locale}');

        $routeCollectionMock = $this->createMock(RouteCollection::class);
        $routeCollectionMock->expects($this->once())
            ->method('get')
            ->willReturn($routeMock);

        $this->router->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollectionMock);

        $this->logger->expects($this->once())
            ->method('info');

        $this->translationControllerMock->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with([], 'en')
            ->willReturn('test');

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en']);

        $this->dumper->dumpTranslations();
    }

    public function testDumpTranslationsWithLocales()
    {
        $routeMock = $this->createMock(Route::class);
        $routeMock->expects($this->once())
            ->method('getPath')
            ->willReturn($this->getTempDir('js_trans_dumper', null) . '/test{_locale}');

        $routeCollectionMock = $this->createMock(RouteCollection::class);
        $routeCollectionMock->expects($this->once())
            ->method('get')
            ->willReturn($routeMock);

        $this->router->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollectionMock);

        $this->logger->expects($this->once())
            ->method('info');

        $this->translationControllerMock->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with([], 'en_US')
            ->willReturn('test');

        $this->languageProvider->expects($this->never())
            ->method('getAvailableLanguageCodes');

        $this->dumper->dumpTranslations(['en_US']);
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\TranslationBundle\Controller\Controller;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class JsTranslationDumperTest extends \PHPUnit_Framework_TestCase
{
    /** @var Controller|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationControllerMock;

    /** @var Router|\PHPUnit_Framework_MockObject_MockObject */
    protected $routerMock;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var LanguageProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $languageProvider;

    /** @var JsTranslationDumper */
    protected $dumper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translationControllerMock = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Controller\Controller')
            ->disableOriginalConstructor()
            ->getMock();

        $this->routerMock = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMock('Psr\Log\LoggerInterface');

        $this->languageProvider = $this->getMockBuilder(LanguageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dumper = new JsTranslationDumper(
            $this->translationControllerMock,
            $this->routerMock,
            [],
            '',
            $this->languageProvider
        );
        $this->dumper->setLogger($this->logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->translationControllerMock,
            $this->routerMock,
            $this->logger,
            $this->languageProvider,
            $this->dumper
        );
    }

    public function testDumpTranslations()
    {
        $routeMock = $this->getMockBuilder('Symfony\Component\Routing\Route')
            ->disableOriginalConstructor()
            ->getMock();
        $routeMock->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('/tmp/test{_locale}'));

        $routeCollectionMock = $this->getMock('Symfony\Component\Routing\RouteCollection');
        $routeCollectionMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($routeMock));

        $this->routerMock->expects($this->once())
            ->method('getRouteCollection')
            ->will($this->returnValue($routeCollectionMock));

        $this->logger->expects($this->once())
            ->method('info');

        $this->translationControllerMock->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with([], 'en')
            ->will($this->returnValue('test'));

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguages')
            ->willReturn(['en' => 'en']);

        $this->dumper->dumpTranslations();
    }

    public function testDumpTranslationsWithLocales()
    {
        $routeMock = $this->getMockBuilder('Symfony\Component\Routing\Route')
            ->disableOriginalConstructor()
            ->getMock();
        $routeMock->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('/tmp/test{_locale}'));

        $routeCollectionMock = $this->getMock('Symfony\Component\Routing\RouteCollection');
        $routeCollectionMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($routeMock));

        $this->routerMock->expects($this->once())
            ->method('getRouteCollection')
            ->will($this->returnValue($routeCollectionMock));

        $this->logger->expects($this->once())
            ->method('info');

        $this->translationControllerMock->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with([], 'en_US')
            ->will($this->returnValue('test'));

        $this->languageProvider->expects($this->never())->method('getAvailableLanguages');

        $this->dumper->dumpTranslations(['en_US']);
    }
}

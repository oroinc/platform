<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;

class JsTranslationDumperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translationControllerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $routerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var JsTranslationDumper */
    protected $dumper;

    public function setUp()
    {
        $this->translationControllerMock = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Controller\Controller')
            ->disableOriginalConstructor()
            ->getMock();

        $this->routerMock = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMock('Psr\Log\LoggerInterface');

        $this->dumper = new JsTranslationDumper($this->translationControllerMock, $this->routerMock, [], '', 'en');
        $this->dumper->setLogger($this->logger);
    }

    public function tearDown()
    {
        unset($this->translationControllerMock, $this->routerMock, $this->logger, $this->dumper);
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
            ->will($this->returnValue('test'));

        $this->dumper->dumpTranslations();
    }
}

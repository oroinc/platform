<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Oro\Bundle\DistributionBundle\EventListener\LocaleListener;
use Oro\Bundle\DistributionBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LocaleListenerTest extends \PHPUnit\Framework\TestCase
{
    const LANG = 'de';

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var RequestContext */
    protected $requestContext;

    /** @var RequestContextAwareInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** @var Request */
    protected $request;

    /** @var LocaleListener */
    protected $listener;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())->method('getConnection')->willReturn($this->connection);

        $this->requestContext = new RequestContext();

        $this->router = $this->createMock(RequestContextAwareInterface::class);
        $this->router->expects($this->any())->method('getContext')->willReturn($this->requestContext);

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->container = $this->createMock(ContainerInterface::class);

        $this->event = $this->createMock(GetResponseEvent::class);
        $this->request = new Request();

        $this->listener = new LocaleListener($this->container);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                KernelEvents::REQUEST => [
                    ['onKernelRequest', 7]
                ],
            ],
            LocaleListener::getSubscribedEvents()
        );
    }

    public function testOnKernelRequestWithNotValidTranslatorClass()
    {
        $this->setUpContainerParameters(\stdClass::class);

        $this->event->expects($this->never())->method($this->anything());

        $this->listener->onKernelRequest($this->event);

        $this->assertRequestLanguage(LocaleListener::DEFAULT_LANGUAGE, null);
    }

    public function testOnKernelRequestWithoutRequest()
    {
        $this->setUpContainerParameters();

        $this->event->expects($this->once())->method('getRequest')->willReturn(null);

        $this->listener->onKernelRequest($this->event);

        $this->assertRequestLanguage(LocaleListener::DEFAULT_LANGUAGE, null);
    }

    public function testOnKernelRequestNotInstalled()
    {
        $this->setUpContainerParameters(Translator::class, null);

        $this->event->expects($this->once())->method('getRequest')->willReturn($this->request);

        $this->listener->onKernelRequest($this->event);

        $this->assertRequestLanguage(LocaleListener::DEFAULT_LANGUAGE, null);
    }

    public function testOnKernelRequestWithoutLanguage()
    {
        $this->setUpContainerParameters();
        $this->setUpContainerServices();

        $this->event->expects($this->once())->method('getRequest')->willReturn($this->request);

        $this->connection->expects($this->once())->method('fetchColumn')->willReturn(null);

        $this->translator->expects($this->once())->method('setLocale')->willReturn(LocaleListener::DEFAULT_LANGUAGE);

        $this->listener->onKernelRequest($this->event);

        $this->assertRequestLanguage(LocaleListener::DEFAULT_LANGUAGE, LocaleListener::DEFAULT_LANGUAGE);
    }

    public function testOnKernelRequestWithoutRouter()
    {
        $this->setUpContainerParameters();
        $this->setUpContainerServices(false);

        $this->event->expects($this->once())->method('getRequest')->willReturn($this->request);

        $this->connection->expects($this->once())->method('fetchColumn')->willReturn(self::LANG);

        $this->translator->expects($this->once())->method('setLocale')->willReturn(LocaleListener::DEFAULT_LANGUAGE);

        $this->listener->onKernelRequest($this->event);

        $this->assertRequestLanguage(self::LANG, null);
    }

    public function testOnKernelRequestWithAttribute()
    {
        $this->setUpContainerParameters();
        $this->setUpContainerServices();

        $this->event->expects($this->once())->method('getRequest')->willReturn($this->request);

        $this->connection->expects($this->once())->method('fetchColumn')->willReturn(self::LANG);

        $this->translator->expects($this->once())->method('setLocale')->willReturn(LocaleListener::DEFAULT_LANGUAGE);

        $this->request->attributes->set('_locale', 'it');

        $this->listener->onKernelRequest($this->event);

        $this->assertRequestLanguage(LocaleListener::DEFAULT_LANGUAGE, null);
    }

    public function testOnKernelRequest()
    {
        $this->setUpContainerParameters();
        $this->setUpContainerServices();

        $this->event->expects($this->once())->method('getRequest')->willReturn($this->request);

        $this->connection->expects($this->once())->method('fetchColumn')->willReturn(self::LANG);

        $this->translator->expects($this->once())->method('setLocale')->willReturn(LocaleListener::DEFAULT_LANGUAGE);

        $this->listener->onKernelRequest($this->event);

        $this->assertRequestLanguage(self::LANG, self::LANG);
    }

    /**
     * @param string $translatorClass
     * @param string $installed
     */
    protected function setUpContainerParameters(
        $translatorClass = Translator::class,
        $installed = '2017-01-01T00:00:00+00:00'
    ) {
        $this->container->expects($this->any())
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['translator.class', $translatorClass],
                    ['installed', $installed]
                ]
            );
    }

    /**
     * @param bool $withRouter
     */
    protected function setUpContainerServices($withRouter = true)
    {
        $services = [
            ['doctrine', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->registry],
            ['translator', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->translator]
        ];

        if ($withRouter) {
            $services = array_merge(
                $services,
                [
                    ['router', ContainerInterface::NULL_ON_INVALID_REFERENCE, $this->router]
                ]
            );
        }

        $this->container->expects($this->any())->method('get')->willReturnMap($services);
    }

    /**
     * @param string $requestLang
     * @param string $requestContextLang
     */
    protected function assertRequestLanguage($requestLang, $requestContextLang)
    {
        $this->assertEquals($requestLang, $this->request->getLocale());
        $this->assertEquals($requestContextLang, $this->requestContext->getParameter('_locale'));
    }
}

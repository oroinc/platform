<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\EventListener;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\SoapBundle\EventListener\LocaleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleListenerTest extends TestCase
{
    private string $defaultLocale;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
    }

    #[\Override]
    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
    }

    public function testOnKernelRequest(): void
    {
        $customLocale = 'fr';

        $request = new Request(['locale' => $customLocale]);
        $request->server->set('REQUEST_URI', '/api/rest/test');
        $request->setDefaultLocale($this->defaultLocale);

        $translationListener = new TranslatableListener();

        $listener = new LocaleListener($translationListener);
        $listener->onKernelRequest($this->createRequestEvent($request));

        self::assertEquals($customLocale, $request->getLocale());
        self::assertEquals($customLocale, $translationListener->getListenerLocale());
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $event = $this->createMock(RequestEvent::class);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        return $event;
    }
}

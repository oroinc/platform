<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme;

use Oro\Component\Layout\Exception\NotRequestContextRuntimeException;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CurrentThemeProviderTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private CurrentThemeProvider $currentThemeProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->currentThemeProvider = new CurrentThemeProvider($this->requestStack);
    }

    public function testSetCurrentRequest(): void
    {
        $request = new Request();
        $this->currentThemeProvider->setCurrentRequest($request);
        $this->assertSame($request, $this->currentThemeProvider->getCurrentRequest());
    }

    public function testGetCurrentThemeIdWithEmulatedThemeId(): void
    {
        $this->currentThemeProvider->emulateThemeId('emulatedTheme');
        $this->assertEquals('emulatedTheme', $this->currentThemeProvider->getCurrentThemeId());
    }

    public function testGetCurrentThemeIdWithCurrentRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_theme', 'testTheme');
        $this->currentThemeProvider->setCurrentRequest($request);
        $this->assertEquals('testTheme', $this->currentThemeProvider->getCurrentThemeId());
    }

    public function testGetCurrentThemeIdWithMainRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_theme', 'mainTheme');
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($request);
        $this->assertEquals('mainTheme', $this->currentThemeProvider->getCurrentThemeId());
    }

    public function testGetCurrentThemeIdWithoutRequest(): void
    {
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn(null);
        $this->expectException(NotRequestContextRuntimeException::class);
        $this->currentThemeProvider->getCurrentThemeId();
    }

    public function testEmulationHasHigherPriorityThenForcedCurrentRequest(): void
    {
        $this->currentThemeProvider->emulateThemeId('emulatedTheme');
        $request = new Request();
        $request->attributes->set('_theme', 'testTheme');
        $this->currentThemeProvider->setCurrentRequest($request);
        $this->assertEquals('emulatedTheme', $this->currentThemeProvider->getCurrentThemeId());
    }
}

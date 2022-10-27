<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Csrf;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfRequestManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CsrfTokenManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $csrfTokenManager;

    /** @var CsrfRequestManager */
    private $manager;

    protected function setUp(): void
    {
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->manager = new CsrfRequestManager($this->csrfTokenManager);
    }

    public function testRefreshRequestToken()
    {
        $this->csrfTokenManager->expects($this->once())
            ->method('refreshToken')
            ->with(CsrfRequestManager::CSRF_TOKEN_ID);

        $this->manager->refreshRequestToken();
    }

    public function testIsRequestTokenValidForRequestValue()
    {
        $request = Request::create('/');
        $value = 'test';
        $request->query->set(CsrfRequestManager::CSRF_TOKEN_ID, $value);

        $this->csrfTokenManager->expects($this->once())
            ->method('isTokenValid')
            ->with(new CsrfToken(CsrfRequestManager::CSRF_TOKEN_ID, $value))
            ->willReturn(true);

        $this->assertTrue($this->manager->isRequestTokenValid($request, true));
    }

    public function testIsRequestTokenValidForHeader()
    {
        $request = Request::create('/');
        $value = 'test';
        $request->headers->set(CsrfRequestManager::CSRF_HEADER, $value);

        $this->csrfTokenManager->expects($this->once())
            ->method('isTokenValid')
            ->with(new CsrfToken(CsrfRequestManager::CSRF_TOKEN_ID, $value))
            ->willReturn(true);

        $this->assertTrue($this->manager->isRequestTokenValid($request));
    }

    public function testIsRequestTokenValidForEmptyRequestValue()
    {
        $request = Request::create('/');

        $this->csrfTokenManager->expects($this->never())
            ->method('isTokenValid');

        $this->assertFalse($this->manager->isRequestTokenValid($request, true));
    }

    public function testIsRequestTokenValidForEmptyHeader()
    {
        $request = Request::create('/');

        $this->csrfTokenManager->expects($this->never())
            ->method('isTokenValid');

        $this->assertFalse($this->manager->isRequestTokenValid($request));
    }
}

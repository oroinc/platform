<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Csrf;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfRequestManagerTest extends \PHPUnit\Framework\TestCase
{
    const TOKEN_ID = 'TOKEN_ID';

    /**
     * @var CsrfTokenManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrfTokenManager;

    /**
     * @var string
     */
    private $tokenId;

    /**
     * @var CsrfRequestManager
     */
    private $manager;

    protected function setUp()
    {
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->tokenId = self::TOKEN_ID;

        $this->manager = new CsrfRequestManager(
            $this->csrfTokenManager,
            $this->tokenId
        );
    }

    public function testRefreshRequestToken()
    {
        $this->csrfTokenManager->expects($this->once())
            ->method('refreshToken')
            ->with($this->tokenId);

        $this->manager->refreshRequestToken();
    }

    public function testIsRequestTokenValidForRequestValue()
    {
        $request = Request::create('/');
        $value = 'test';
        $request->query->set($this->tokenId, $value);

        $this->csrfTokenManager->expects($this->once())
            ->method('isTokenValid')
            ->with(new CsrfToken($this->tokenId, $value))
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
            ->with(new CsrfToken($this->tokenId, $value))
            ->willReturn(true);

        $this->assertTrue($this->manager->isRequestTokenValid($request, false));
    }
}

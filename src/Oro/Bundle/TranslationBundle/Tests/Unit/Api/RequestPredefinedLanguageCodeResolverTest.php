<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Api;

use Oro\Bundle\TranslationBundle\Api\RequestPredefinedLanguageCodeResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestPredefinedLanguageCodeResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var RequestPredefinedLanguageCodeResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->resolver = new RequestPredefinedLanguageCodeResolver($this->requestStack);
    }

    public function testDescription(): void
    {
        self::assertEquals(
            '**current** for a language of the current request.',
            $this->resolver->getDescription()
        );
    }

    public function testResolveWhenNoCurrentRequest(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        self::assertEquals('en', $this->resolver->resolve());
    }

    public function testResolveWhenCurrentRequestExists(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getLocale')
            ->willReturn('en_CA');
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        self::assertEquals('en_CA', $this->resolver->resolve());
    }
}

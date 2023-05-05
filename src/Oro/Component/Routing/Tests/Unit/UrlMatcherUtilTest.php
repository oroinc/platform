<?php

namespace Oro\Component\Routing\Tests\Unit;

use Oro\Component\Routing\UrlMatcherUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class UrlMatcherUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testMatchForGetMethod()
    {
        $pathInfo = '/pathInfo';
        $contextPathInfo = '/contextPathInfo';
        $contextMethod = Request::METHOD_POST;
        $attributes = ['_controller' => 'TestController'];

        $context = new RequestContext();
        $context->setPathInfo($contextPathInfo);
        $context->setMethod($contextMethod);

        $urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $urlMatcher->expects(self::once())
            ->method('getContext')
            ->willReturn($context);
        $urlMatcher->expects(self::once())
            ->method('match')
            ->with($pathInfo)
            ->willReturnCallback(function () use ($context, $pathInfo, $attributes) {
                self::assertEquals($pathInfo, $context->getPathInfo());
                self::assertEquals(Request::METHOD_GET, $context->getMethod());

                return $attributes;
            });

        self::assertSame(
            $attributes,
            UrlMatcherUtil::matchForGetMethod($pathInfo, $urlMatcher)
        );
        self::assertEquals($contextPathInfo, $context->getPathInfo());
        self::assertEquals($contextMethod, $context->getMethod());
    }

    public function testMatchForGetMethodWhenExceptionOccurred()
    {
        $this->expectException(ResourceNotFoundException::class);
        $pathInfo = '/pathInfo';
        $contextPathInfo = '/contextPathInfo';
        $contextMethod = Request::METHOD_POST;

        $context = new RequestContext();
        $context->setPathInfo($contextPathInfo);
        $context->setMethod($contextMethod);

        $urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $urlMatcher->expects(self::once())
            ->method('getContext')
            ->willReturn($context);
        $urlMatcher->expects(self::once())
            ->method('match')
            ->with($pathInfo)
            ->willReturnCallback(function () use ($context, $pathInfo) {
                self::assertEquals($pathInfo, $context->getPathInfo());
                self::assertEquals(Request::METHOD_GET, $context->getMethod());

                throw new ResourceNotFoundException();
            });

        try {
            UrlMatcherUtil::matchForGetMethod($pathInfo, $urlMatcher);
            self::fail('Failed asserting that the exception occurred.');
        } catch (ResourceNotFoundException $e) {
            self::assertEquals($contextPathInfo, $context->getPathInfo());
            self::assertEquals($contextMethod, $context->getMethod());

            throw $e;
        }
    }
}

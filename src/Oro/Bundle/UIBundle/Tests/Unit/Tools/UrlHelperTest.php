<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\UrlHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper as SymfonyUrlHelper;
use Symfony\Component\Routing\RequestContext;

class UrlHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAbsoluteUrl(): void
    {
        $requestStack = new RequestStack();
        $symfonyUrlHelper = new SymfonyUrlHelper($requestStack);
        $urlHelper = new UrlHelper($symfonyUrlHelper, $requestStack);

        self::assertSame(
            $symfonyUrlHelper->getAbsoluteUrl('/sample/path'),
            $urlHelper->getAbsoluteUrl('/sample/path')
        );
    }

    public function testGetRelativePath(): void
    {
        $requestStack = new RequestStack();
        $symfonyUrlHelper = new SymfonyUrlHelper($requestStack);
        $urlHelper = new UrlHelper($symfonyUrlHelper, $requestStack);

        self::assertSame(
            $symfonyUrlHelper->getRelativePath('/sample/path'),
            $urlHelper->getRelativePath('/sample/path')
        );
    }

    public function testGetAbsolutePathWhenNoRequestNoContext(): void
    {
        $requestStack = new RequestStack();
        $requestContext = null;
        $urlHelper = new UrlHelper(
            new SymfonyUrlHelper($requestStack, $requestContext),
            $requestStack,
            $requestContext
        );

        self::assertEquals('/sample/path', $urlHelper->getAbsolutePath('/sample/path'));
    }

    /**
     * @dataProvider getAbsolutePathDataProvider
     */
    public function testGetAbsolutePathWhenNoRequestButHasContext(string $path, string $expectedUrl): void
    {
        $requestStack = new RequestStack();
        $requestContext = new RequestContext('/base/url/');
        $requestContext->setPathInfo('/base/url/');
        $urlHelper = new UrlHelper(
            new SymfonyUrlHelper($requestStack, $requestContext),
            $requestStack,
            $requestContext
        );

        self::assertEquals($expectedUrl, $urlHelper->getAbsolutePath($path));
    }

    public function getAbsolutePathDataProvider(): array
    {
        return [
            ['', '/base/url/'],
            ['/', '/base/url/'],
            ['sample/path', '/base/url/sample/path'],
            ['/sample/path', '/base/url/sample/path'],
        ];
    }

    /**
     * @dataProvider getAbsolutePathDataProvider
     */
    public function testGetAbsolutePathWhenRequest(string $path, string $expectedUrl): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(
            Request::create(
                '/base/url/',
                'GET',
                [],
                [],
                [],
                ['SCRIPT_FILENAME' => '/base/url/', 'SCRIPT_NAME' => '/base/url/']
            )
        );
        $requestContext = null;
        $urlHelper = new UrlHelper(
            new SymfonyUrlHelper($requestStack, $requestContext),
            $requestStack,
            $requestContext
        );

        self::assertEquals($expectedUrl, $urlHelper->getAbsolutePath($path));
    }
}

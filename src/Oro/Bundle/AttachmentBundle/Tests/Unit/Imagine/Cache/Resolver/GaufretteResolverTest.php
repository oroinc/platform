<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Oro\Bundle\AttachmentBundle\Imagine\Cache\Resolver\GaufretteResolver;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\Routing\RequestContext;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GaufretteResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject */
    private $requestContext;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->requestContext = $this->createMock(RequestContext::class);
    }

    private function getResolver(string $urlPrefix = 'media/cache', string $cachePrefix = ''): GaufretteResolver
    {
        return new GaufretteResolver($this->fileManager, $this->requestContext, $urlPrefix, $cachePrefix);
    }

    public function testShouldImplementResolverInterface(): void
    {
        self::assertInstanceOf(ResolverInterface::class, $this->getResolver());
    }

    public function testCustomUrlPrefix(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('http');
        $this->requestContext->expects(self::once())
            ->method('getHttpPort')
            ->willReturn(80);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('');

        $resolver = $this->getResolver('testUrlPrefix');

        self::assertSame(
            'http://localhost/testUrlPrefix/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testCustomCachePrefix(): void
    {
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with('testCachePrefix/filter1/file.jpg')
            ->willReturn(false);

        $resolver = $this->getResolver('media/cache', 'testCachePrefix');

        $resolver->isStored('file.jpg', 'filter1');
    }

    public function testTrimRightSlashFromUrlPrefix(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('http');
        $this->requestContext->expects(self::once())
            ->method('getHttpPort')
            ->willReturn(80);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('');

        $resolver = $this->getResolver('testUrlPrefix/');

        self::assertSame(
            'http://localhost/testUrlPrefix/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testTrimLeftSlashFromCachePrefix(): void
    {
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with('testCachePrefix/filter1/file.jpg')
            ->willReturn(false);

        $resolver = $this->getResolver('testUrlPrefix', '/testCachePrefix');

        $resolver->isStored('file.jpg', 'filter1');
    }

    public function testResolveForHttp(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('http');
        $this->requestContext->expects(self::once())
            ->method('getHttpPort')
            ->willReturn(80);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('');

        $resolver = $this->getResolver();

        self::assertSame(
            'http://localhost/media/cache/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testResolveForHttpWithNotStandardPort(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('http');
        $this->requestContext->expects(self::atLeastOnce())
            ->method('getHttpPort')
            ->willReturn(81);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('');

        $resolver = $this->getResolver();

        self::assertSame(
            'http://localhost:81/media/cache/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testResolveForHttps(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('https');
        $this->requestContext->expects(self::once())
            ->method('getHttpsPort')
            ->willReturn(443);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('');

        $resolver = $this->getResolver();

        self::assertSame(
            'https://localhost/media/cache/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testResolveForHttpsWithNotStandardPort(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('https');
        $this->requestContext->expects(self::atLeastOnce())
            ->method('getHttpsPort')
            ->willReturn(444);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('');

        $resolver = $this->getResolver();

        self::assertSame(
            'https://localhost:444/media/cache/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testResolveWithBaseUrl(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('http');
        $this->requestContext->expects(self::once())
            ->method('getHttpPort')
            ->willReturn(80);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('/testBaseUrl');

        $resolver = $this->getResolver();

        self::assertSame(
            'http://localhost/testBaseUrl/media/cache/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testResolveWithPhpFileInBaseUrl(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('http');
        $this->requestContext->expects(self::once())
            ->method('getHttpPort')
            ->willReturn(80);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('/index.php');

        $resolver = $this->getResolver();

        self::assertSame(
            'http://localhost/media/cache/filter1/file.jpg',
            $resolver->resolve('file.jpg', 'filter1')
        );
    }

    public function testResolveForPathContainsHttpSchema(): void
    {
        $this->requestContext->expects(self::once())
            ->method('getScheme')
            ->willReturn('http');
        $this->requestContext->expects(self::once())
            ->method('getHttpPort')
            ->willReturn(80);
        $this->requestContext->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('');

        $resolver = $this->getResolver();

        self::assertSame(
            'http://localhost/media/cache/filter1/http---localhost/test/file.jpg',
            $resolver->resolve('http://localhost/test/file.jpg', 'filter1')
        );
    }

    public function testIsStoredWhenNoFileInStorage(): void
    {
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with('filter1/file.jpg')
            ->willReturn(false);

        $resolver = $this->getResolver();

        self::assertFalse($resolver->isStored('file.jpg', 'filter1'));
    }

    public function testIsStoredWhenThereIsFileInStorage(): void
    {
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with('filter1/file.jpg')
            ->willReturn(true);

        $resolver = $this->getResolver();

        self::assertTrue($resolver->isStored('file.jpg', 'filter1'));
    }

    public function testIsStoredWithCustomCachePrefix(): void
    {
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with('testCachePrefix/filter1/file.jpg')
            ->willReturn(true);

        $resolver = $this->getResolver('media/cache', 'testCachePrefix');

        self::assertTrue($resolver->isStored('file.jpg', 'filter1'));
    }

    public function testStore(): void
    {
        $context = 'testContext';
        $binary = $this->createMock(BinaryInterface::class);
        $binary->expects(self::once())
            ->method('getContent')
            ->willReturn($context);

        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($context, 'filter1/file.jpg');

        $resolver = $this->getResolver();

        $resolver->store($binary, 'file.jpg', 'filter1');
    }

    public function testStoreWithCustomCachePrefix(): void
    {
        $context = 'testContext';
        $binary = $this->createMock(BinaryInterface::class);
        $binary->expects(self::once())
            ->method('getContent')
            ->willReturn($context);

        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($context, 'testCachePrefix/filter1/file.jpg');

        $resolver = $this->getResolver('media/cache', 'testCachePrefix');

        $resolver->store($binary, 'file.jpg', 'filter1');
    }

    public function testRemoveWithoutPathsAndFilters(): void
    {
        $this->fileManager->expects(self::never())
            ->method(self::anything());

        $resolver = $this->getResolver();

        $resolver->remove([], []);
    }

    public function testRemoveWithoutFilters(): void
    {
        $this->fileManager->expects(self::never())
            ->method(self::anything());

        $resolver = $this->getResolver();

        $resolver->remove(['file1.jpg', 'file2.jpg'], []);
    }

    public function testRemoveWithoutPaths(): void
    {
        $this->fileManager->expects(self::exactly(2))
            ->method('deleteAllFiles')
            ->withConsecutive(
                ['filter1/'],
                ['filter2/']
            );
        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $resolver = $this->getResolver();

        $resolver->remove([], ['filter1', 'filter2']);
    }

    public function testRemoveWithoutPathsAndWithCustomCachePrefix(): void
    {
        $this->fileManager->expects(self::exactly(2))
            ->method('deleteAllFiles')
            ->withConsecutive(
                ['testCachePrefix/filter1/'],
                ['testCachePrefix/filter2/']
            );
        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $resolver = $this->getResolver('media/cache', 'testCachePrefix');

        $resolver->remove([], ['filter1', 'filter2']);
    }

    public function testRemoveWithPathsAndFilters(): void
    {
        $this->fileManager->expects(self::exactly(4))
            ->method('deleteFile')
            ->withConsecutive(
                ['filter1/file1.jpg'],
                ['filter2/file1.jpg'],
                ['filter1/file2.jpg'],
                ['filter2/file2.jpg']
            );
        $this->fileManager->expects(self::never())
            ->method('deleteAllFiles');

        $resolver = $this->getResolver();

        $resolver->remove(['file1.jpg', 'file2.jpg'], ['filter1', 'filter2']);
    }

    public function testRemoveWithPathsAndFiltersAndWithCustomCachePrefix(): void
    {
        $this->fileManager->expects(self::exactly(4))
            ->method('deleteFile')
            ->withConsecutive(
                ['testCachePrefix/filter1/file1.jpg'],
                ['testCachePrefix/filter2/file1.jpg'],
                ['testCachePrefix/filter1/file2.jpg'],
                ['testCachePrefix/filter2/file2.jpg']
            );
        $this->fileManager->expects(self::never())
            ->method('deleteAllFiles');

        $resolver = $this->getResolver('media/cache', 'testCachePrefix');

        $resolver->remove(['file1.jpg', 'file2.jpg'], ['filter1', 'filter2']);
    }
}

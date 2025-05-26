<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;

class NextPageLinkMetadataTest extends TestCase
{
    public function testGetHrefWhenThereIsNoMoreRecords(): void
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $linkMetadata = new NextPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com'),
            'page[number]',
            $queryStringAccessor
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->willReturn(false);
        $queryStringAccessor->expects(self::never())
            ->method('getQueryString');

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    public function testGetHrefWhenPaginationIsNotSupported(): void
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $linkMetadata = new NextPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com'),
            'page[number]',
            $queryStringAccessor
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::HAS_MORE === $propertyPath) {
                    $value = true;
                    $hasValue = true;
                }

                return $hasValue;
            });
        $queryStringAccessor->expects(self::never())
            ->method('getQueryString');

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    public function testGetHrefForFirstPage(): void
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $linkMetadata = new NextPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com'),
            'page[number]',
            $queryStringAccessor
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::HAS_MORE === $propertyPath) {
                    $value = true;
                    $hasValue = true;
                } elseif (ConfigUtil::PAGE_NUMBER === $propertyPath) {
                    $value = 1;
                    $hasValue = true;
                }

                return $hasValue;
            });
        $queryStringAccessor->expects(self::once())
            ->method('getQueryString')
            ->willReturn('sort=id');

        self::assertEquals(
            'http://test.com?sort=id&page%5Bnumber%5D=2',
            $linkMetadata->getHref($dataAccessor)
        );
    }

    public function testGetHrefForAnotherPage(): void
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $linkMetadata = new NextPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com?filter=val'),
            'page[number]',
            $queryStringAccessor
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::HAS_MORE === $propertyPath) {
                    $value = true;
                    $hasValue = true;
                } elseif (ConfigUtil::PAGE_NUMBER === $propertyPath) {
                    $value = 3;
                    $hasValue = true;
                }

                return $hasValue;
            });
        $queryStringAccessor->expects(self::once())
            ->method('getQueryString')
            ->willReturn('page[number]=3&sort=id');

        self::assertEquals(
            'http://test.com?filter=val&page%5Bnumber%5D=4&sort=id',
            $linkMetadata->getHref($dataAccessor)
        );
    }

    public function testGetHrefWhenNoQueryStringAccessor(): void
    {
        $linkMetadata = new NextPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com'),
            'page[number]'
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::HAS_MORE === $propertyPath) {
                    $value = true;
                    $hasValue = true;
                } elseif (ConfigUtil::PAGE_NUMBER === $propertyPath) {
                    $value = 2;
                    $hasValue = true;
                }

                return $hasValue;
            });

        self::assertEquals(
            'http://test.com?page%5Bnumber%5D=3',
            $linkMetadata->getHref($dataAccessor)
        );
    }

    public function testGetHrefWhenNoPageNumberInData(): void
    {
        $linkMetadata = new NextPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com'),
            'page[number]'
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::HAS_MORE === $propertyPath) {
                    $value = true;
                    $hasValue = true;
                }

                return $hasValue;
            });

        self::assertEquals(
            'http://test.com?page%5Bnumber%5D=2',
            $linkMetadata->getHref($dataAccessor)
        );
    }
}

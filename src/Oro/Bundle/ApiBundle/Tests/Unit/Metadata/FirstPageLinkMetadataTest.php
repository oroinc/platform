<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\FirstPageLinkMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FirstPageLinkMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHrefWhenPaginationIsNotSupported()
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $linkMetadata = new FirstPageLinkMetadata(
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

    public function testGetHrefForFirstPage()
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $linkMetadata = new FirstPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com'),
            'page[number]',
            $queryStringAccessor
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::PAGE_NUMBER === $propertyPath) {
                    $value = 1;
                    $hasValue = true;
                }

                return $hasValue;
            });
        $queryStringAccessor->expects(self::never())
            ->method('getQueryString');

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    public function testGetHrefForAnotherPage()
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $linkMetadata = new FirstPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com?filter=val'),
            'page[number]',
            $queryStringAccessor
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::PAGE_NUMBER === $propertyPath) {
                    $value = 2;
                    $hasValue = true;
                }

                return $hasValue;
            });
        $queryStringAccessor->expects(self::once())
            ->method('getQueryString')
            ->willReturn('page[number]=2&sort=id');

        self::assertEquals(
            'http://test.com?filter=val&sort=id',
            $linkMetadata->getHref($dataAccessor)
        );
    }

    public function testGetHrefWhenNoQueryStringAccessor()
    {
        $linkMetadata = new FirstPageLinkMetadata(
            new ExternalLinkMetadata('http://test.com'),
            'page[number]'
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (ConfigUtil::PAGE_NUMBER === $propertyPath) {
                    $value = 2;
                    $hasValue = true;
                }

                return $hasValue;
            });

        self::assertEquals(
            'http://test.com',
            $linkMetadata->getHref($dataAccessor)
        );
    }
}

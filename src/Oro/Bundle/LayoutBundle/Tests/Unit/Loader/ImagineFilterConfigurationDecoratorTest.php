<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Loader;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Loader\ImagineFilterConfigurationDecorator;
use Oro\Component\DependencyInjection\ServiceLink;

class ImagineFilterConfigurationDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $filterConfiguration;

    /** @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject */
    private $filterLoaderServiceLink;

    /** @var ImagineFilterConfigurationDecorator */
    private $imagineFilterConfigurationDecorator;

    protected function setUp(): void
    {
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);
        $this->filterLoaderServiceLink = $this->createMock(ServiceLink::class);

        $this->imagineFilterConfigurationDecorator = new ImagineFilterConfigurationDecorator(
            $this->filterConfiguration,
            $this->filterLoaderServiceLink
        );
    }

    public function testSet(): void
    {
        $filterName = 'some_filter';
        $filterConfig = ['some' => 'config'];
        $filterResults = ['filter' => 'results'];

        $this->filterLoaderServiceLink->expects(self::never())
            ->method('getService');

        $this->filterConfiguration->expects(self::once())
            ->method('set')
            ->with($filterName, $filterConfig)
            ->willReturn($filterResults);

        $this->imagineFilterConfigurationDecorator->set($filterName, $filterConfig);
    }

    public function testGet(): void
    {
        $filterName = 'some_filter';
        $filterResults = ['filter' => 'results'];

        $filterLoader = $this->createMock(ImageFilterLoader::class);
        $filterLoader->expects(self::once())
            ->method('load');

        $this->filterLoaderServiceLink->expects(self::once())
            ->method('getService')
            ->willReturn($filterLoader);

        $this->filterConfiguration->expects(self::once())
            ->method('get')
            ->with($filterName)
            ->willReturn($filterResults);

        self::assertEquals($filterResults, $this->imagineFilterConfigurationDecorator->get($filterName));
    }

    public function testAll(): void
    {
        $filterResults = ['filter' => 'results'];

        $filterLoader = $this->createMock(ImageFilterLoader::class);
        $filterLoader->expects(self::once())
            ->method('load');

        $this->filterLoaderServiceLink->expects(self::once())
            ->method('getService')
            ->willReturn($filterLoader);

        $this->filterConfiguration->expects(self::once())
            ->method('all')
            ->willReturn($filterResults);

        self::assertEquals($filterResults, $this->imagineFilterConfigurationDecorator->all());
    }
}

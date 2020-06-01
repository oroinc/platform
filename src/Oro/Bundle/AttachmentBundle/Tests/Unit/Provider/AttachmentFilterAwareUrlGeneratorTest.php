<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentFilterAwareUrlGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class AttachmentFilterAwareUrlGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /**
     * @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlGenerator;

    /**
     * @var FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filterConfiguration;

    /**
     * @var AttachmentFilterAwareUrlGenerator
     */
    private $filterAwareGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);

        $this->filterAwareGenerator = new AttachmentFilterAwareUrlGenerator(
            $this->urlGenerator,
            $this->filterConfiguration
        );

        $this->setUpLoggerMock($this->filterAwareGenerator);
    }

    public function testSetContext()
    {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(RequestContext::class);
        $this->urlGenerator->expects($this->once())
            ->method('setContext')
            ->with($context);

        $this->filterAwareGenerator->setContext($context);
    }

    public function testGetContext()
    {
        $context = $this->createMock(RequestContext::class);
        $this->urlGenerator->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $this->assertSame($context, $this->filterAwareGenerator->getContext());
    }

    public function testGenerateWithoutFilter()
    {
        $route = 'test';
        $parameters = ['id' => 1];

        $this->filterConfiguration->expects($this->never())
            ->method($this->anything());

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, $parameters)
            ->willReturn('/test/1');
        $this->assertSame('/test/1', $this->filterAwareGenerator->generate($route, $parameters));
    }

    public function testGenerateWithFilter()
    {
        $route = 'test';
        $parameters = ['id' => 1, 'filter' => 'test_filter'];

        $filterConfig = ['size' => ['height' => 'auto']];
        $this->filterConfiguration->expects($this->once())
            ->method('get')
            ->with('test_filter')
            ->willReturn($filterConfig);
        $filterMd5 = md5(json_encode($filterConfig));

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, ['id' => 1, 'filter' => 'test_filter', 'filterMd5' => $filterMd5])
            ->willReturn('/test/1');
        $this->assertSame('/test/1', $this->filterAwareGenerator->generate($route, $parameters));
    }

    public function testGenerateWhenException()
    {
        $route = 'test';
        $parameters = ['id' => 1];

        $this->filterConfiguration->expects($this->never())
            ->method($this->anything());

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, $parameters)
            ->willThrowException(new InvalidParameterException());

        $this->assertLoggerWarningMethodCalled();

        $this->assertEquals('', $this->filterAwareGenerator->generate($route, $parameters));
    }

    public function testGenerateWhenGeneratorReturnsNull()
    {
        $route = 'test';
        $parameters = ['id' => 1];

        $this->filterConfiguration->expects($this->never())
            ->method($this->anything());

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, $parameters)
            ->willReturn(null);

        $this->assertLoggerNotCalled();

        $this->assertEquals('', $this->filterAwareGenerator->generate($route, $parameters));
    }

    public function testGetFilterHash()
    {
        $filterName = 'filterName';
        $filterConfig = ['filterConfig'];
        $this->filterConfiguration->expects($this->once())
            ->method('get')
            ->with($filterName)
            ->willReturn($filterConfig);

        $expected = md5(json_encode($filterConfig));

        $this->assertEquals($expected, $this->filterAwareGenerator->getFilterHash($filterName));
    }
}

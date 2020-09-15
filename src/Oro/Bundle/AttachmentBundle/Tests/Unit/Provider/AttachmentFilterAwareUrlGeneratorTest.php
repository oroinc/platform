<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentHashProvider;
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

    /** @var AttachmentHashProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentHashProvider;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->attachmentHashProvider = $this->createMock(AttachmentHashProvider::class);

        $this->filterAwareGenerator = new AttachmentFilterAwareUrlGenerator(
            $this->urlGenerator,
            $this->attachmentHashProvider
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

        $this->attachmentHashProvider
            ->expects($this->never())
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

        $filterHash = md5(json_encode(['size' => ['height' => 'auto']]));

        $this->attachmentHashProvider
            ->expects($this->once())
            ->method('getFilterConfigHash')
            ->willReturn($filterHash);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, ['id' => 1, 'filter' => 'test_filter', 'filterMd5' => $filterHash])
            ->willReturn('/test/1');
        $this->assertSame('/test/1', $this->filterAwareGenerator->generate($route, $parameters));
    }

    public function testGenerateWhenException()
    {
        $route = 'test';
        $parameters = ['id' => 1];

        $this->attachmentHashProvider
            ->expects($this->never())
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

        $this->attachmentHashProvider
            ->expects($this->never())
            ->method($this->anything());

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, $parameters)
            ->willReturn(null);

        $this->assertLoggerNotCalled();

        $this->assertEquals('', $this->filterAwareGenerator->generate($route, $parameters));
    }

    public function testGetFilterHash(): void
    {
        $filterName = 'filterName';
        $filterConfig = md5(json_encode(['filterConfig']));
        $this->attachmentHashProvider
            ->expects($this->once())
            ->method('getFilterConfigHash')
            ->with($filterName)
            ->willReturn($filterConfig);

        $this->assertEquals($filterConfig, $this->filterAwareGenerator->getFilterHash($filterName));
    }
}

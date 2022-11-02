<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Processor;

use Oro\Bundle\EmailBundle\Processor\EntityRouteVariableProcessor;
use Oro\Bundle\EmailBundle\Provider\UrlProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class EntityRouteVariableProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var UrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $urlProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var TemplateData|\PHPUnit\Framework\MockObject\MockObject */
    private $data;

    /** @var EntityRouteVariableProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->urlProvider = $this->createMock(UrlProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->data = $this->createMock(TemplateData::class);

        $this->processor = new EntityRouteVariableProcessor(
            $this->doctrineHelper,
            $this->urlProvider,
            $this->logger
        );
    }

    public function testProcessForRouteThatDoesNotRequireEntityId()
    {
        $variable = 'entity.url_index';
        $parentVariable = 'entity';
        $definition = ['route' => 'route_index'];
        $url = 'http://example.com/entity';

        $this->data->expects(self::once())
            ->method('getParentVariablePath')
            ->with($variable)
            ->willReturn($parentVariable);
        $this->data->expects(self::once())
            ->method('getEntityVariable')
            ->with($parentVariable)
            ->willReturn(new \stdClass());
        $this->data->expects(self::once())
            ->method('setComputedVariable')
            ->with($variable, $url);

        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $this->urlProvider->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with($definition['route'])
            ->willReturn($url);

        $this->processor->process($variable, $definition, $this->data);
    }

    public function testProcessForRouteThatRequiresEntityId()
    {
        $variable = 'entity.url_update';
        $parentVariable = 'entity';
        $definition = ['route' => 'route_update'];
        $entityId = 123;
        $url = 'http://example.com/entity/123';

        $this->data->expects(self::once())
            ->method('getParentVariablePath')
            ->with($variable)
            ->willReturn($parentVariable);
        $this->data->expects(self::once())
            ->method('getEntityVariable')
            ->with($parentVariable)
            ->willReturn(new \stdClass());
        $this->data->expects(self::once())
            ->method('setComputedVariable')
            ->with($variable, $url);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($entityId);

        $this->urlProvider->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with($definition['route'], ['id' => $entityId])
            ->willReturn($url);

        $this->processor->process($variable, $definition, $this->data);
    }

    public function testProcessWithWrongEntity()
    {
        $variable = 'entity.url_update';
        $parentVariable = 'entity';
        $definition = ['route' => 'route_update'];

        $this->data->expects(self::once())
            ->method('getParentVariablePath')
            ->with($variable)
            ->willReturn($parentVariable);
        $this->data->expects(self::once())
            ->method('getEntityVariable')
            ->with($parentVariable)
            ->willReturn(new \stdClass());
        $this->data->expects(self::once())
            ->method('setComputedVariable')
            ->with($variable, self::isNull());

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willThrowException(new \Exception('invalid entity'));

        $this->urlProvider->expects(self::never())
            ->method('getAbsoluteUrl');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(sprintf('The variable "%s" cannot be resolved.', $variable));

        $this->processor->process($variable, $definition, $this->data);
    }

    public function testProcessWithWrongRoute()
    {
        $variable = 'entity.url_index';
        $parentVariable = 'entity';
        $definition = ['route' => 'route_index'];

        $this->data->expects(self::once())
            ->method('getParentVariablePath')
            ->with($variable)
            ->willReturn($parentVariable);
        $this->data->expects(self::once())
            ->method('getEntityVariable')
            ->with($parentVariable)
            ->willReturn(new \stdClass());
        $this->data->expects(self::once())
            ->method('setComputedVariable')
            ->with($variable, self::isNull());

        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $this->urlProvider->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with($definition['route'])
            ->willThrowException(new RouteNotFoundException('unknown route'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(sprintf('The variable "%s" cannot be resolved.', $variable));

        $this->processor->process($variable, $definition, $this->data);
    }
}

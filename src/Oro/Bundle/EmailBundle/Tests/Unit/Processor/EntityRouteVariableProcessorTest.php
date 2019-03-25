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
    /** @var EntityRouteVariableProcessor */
    private $processor;

    /** @var  DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var UrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $urlProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->urlProvider = $this->createMock(UrlProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new EntityRouteVariableProcessor(
            $this->doctrineHelper,
            $this->urlProvider,
            $this->logger
        );
    }

    /**
     * @param array $data
     *
     * @return TemplateData
     */
    private function getTemplateData(array $data): TemplateData
    {
        return new TemplateData($data, 'system', 'entity', 'computed');
    }

    public function testProcessForRouteThatDoesNotRequireEntityId()
    {
        $variable = 'entity.url.index';
        $definition = ['route' => 'route_index'];
        $data = $this->getTemplateData(['entity' => new \stdClass()]);
        $url = 'http://example.com/entity';

        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $this->urlProvider->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with($definition['route'])
            ->willReturn($url);

        $this->processor->process($variable, $definition, $data);

        self::assertEquals($url, $data->getComputedVariable($variable));
    }

    public function testProcessForRouteThatRequiresEntityId()
    {
        $variable = 'entity.url.update';
        $definition = ['route' => 'route_update'];
        $data = $this->getTemplateData(['entity' => new \stdClass()]);
        $entityId = 123;
        $url = 'http://example.com/entity/123';

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($entityId);

        $this->urlProvider->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with($definition['route'], ['id' => $entityId])
            ->willReturn($url);

        $this->processor->process($variable, $definition, $data);

        self::assertEquals($url, $data->getComputedVariable($variable));
    }

    public function testProcessWithWrongEntity()
    {
        $variable = 'entity.url.update';
        $definition = ['route' => 'route_update'];
        $data = $this->getTemplateData(['entity' => new \stdClass()]);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willThrowException(new \Exception('invalid entity'));

        $this->urlProvider->expects(self::never())
            ->method('getAbsoluteUrl');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(sprintf('The variable "%s" cannot be resolved.', $variable));

        $this->processor->process($variable, $definition, $data);

        self::assertNull($data->getComputedVariable($variable));
    }

    public function testProcessWithWrongRoute()
    {
        $variable = 'entity.url.index';
        $definition = ['route' => 'route_index'];
        $data = $this->getTemplateData(['entity' => new \stdClass()]);

        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $this->urlProvider->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with($definition['route'])
            ->willThrowException(new RouteNotFoundException('unknown route'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(sprintf('The variable "%s" cannot be resolved.', $variable));

        $this->processor->process($variable, $definition, $data);

        self::assertNull($data->getComputedVariable($variable));
    }
}

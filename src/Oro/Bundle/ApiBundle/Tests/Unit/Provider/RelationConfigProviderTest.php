<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class RelationConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorInterface */
    private $processor;

    /** @var RelationConfigProvider */
    private $configProvider;

    protected function setUp()
    {
        $this->processor = $this->createMock(ActionProcessorInterface::class);

        $this->configProvider = new RelationConfigProvider($this->processor);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty.
     */
    public function testShouldThrowExceptionIfClassNameIsEmpty()
    {
        $this->configProvider->getRelationConfig('', '1.2', new RequestType([]));
    }

    public function testShouldBuildConfig()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);

        $extra = $this->createMock(ConfigExtraInterface::class);
        $extra->expects(self::any())
            ->method('getName')
            ->willReturn('test_extra');
        $extra->expects(self::any())
            ->method('getCacheKeyPart')
            ->willReturn('test_extra_key');

        $sectionExtra = $this->createMock(ConfigExtraSectionInterface::class);
        $sectionExtra->expects(self::any())
            ->method('getName')
            ->willReturn('test_section_extra');
        $sectionExtra->expects(self::any())
            ->method('getCacheKeyPart')
            ->willReturn('test_section_extra_key');

        $context = new ConfigContext();
        $definition = new EntityDefinitionConfig();

        $this->processor->expects(self::once())
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ConfigContext $context) use (
                $className,
                $version,
                $requestType,
                $extra,
                $sectionExtra,
                $definition
            ) {
                self::assertEquals($className, $context->getClassName());
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType->toArray(), $context->getRequestType()->toArray());
                $extras = $context->getExtras();
                self::assertCount(2, $extras);
                self::assertSame($extra, $extras[0]);
                self::assertSame($sectionExtra, $extras[1]);

                $definition->addField('test_field');
                $context->setResult($definition);
                $context->set('test_section_extra', ['test_section_key' => 'value']);
            });

        $result = $this->configProvider->getRelationConfig(
            $className,
            $version,
            $requestType,
            [$extra, $sectionExtra]
        );
        self::assertEquals('relation|Test\Class|test_extra_key', $result->getDefinition()->getKey());
        self::assertTrue($definition->hasField('test_field'));
        self::assertEquals(['test_section_key' => 'value'], $result->get('test_section_extra'));
        // a clone of definition should be returned
        self::assertNotSame($definition, $result->getDefinition());
        self::assertEquals($definition, $result->getDefinition());

        // test that the config is cached, but its clone should be returned
        $anotherResult = $this->configProvider->getRelationConfig(
            $className,
            $version,
            $requestType,
            [$extra, $sectionExtra]
        );
        self::assertNotSame($result, $anotherResult);
        self::assertEquals($result, $anotherResult);
    }

    public function testShouldBePossibleToClearInternalCache()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);
        $context = new ConfigContext();

        $this->processor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::exactly(2))
            ->method('process')
            ->with(self::identicalTo($context));

        $this->configProvider->getRelationConfig($className, $version, $requestType);

        $this->configProvider->clearCache();
        $this->configProvider->getRelationConfig($className, $version, $requestType);
    }
}

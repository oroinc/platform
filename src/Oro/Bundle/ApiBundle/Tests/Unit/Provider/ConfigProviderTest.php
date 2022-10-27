<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorInterface */
    private $processor;

    /** @var ConfigProvider */
    private $configProvider;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(ActionProcessorInterface::class);

        $this->configProvider = new ConfigProvider($this->processor);
    }

    public function testShouldThrowExceptionIfClassNameIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty.');

        $this->configProvider->getConfig('', '1.2', new RequestType([]));
    }

    public function testShouldBuildConfigAndCacheConfigAndSetConfigKeyForNotIdentifierFieldsOnlyConfig()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);

        $extra = new EntityDefinitionConfigExtra('test');

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
                self::assertTrue($context->has(FilterIdentifierFieldsConfigExtra::NAME));
                self::assertFalse($context->get(FilterIdentifierFieldsConfigExtra::NAME));
                $extras = $context->getExtras();
                self::assertCount(2, $extras);
                self::assertSame($extra, $extras[0]);
                self::assertSame($sectionExtra, $extras[1]);

                $definition->addField('test_field');
                $context->setResult($definition);
                $context->set('test_section_extra', ['test_section_key' => 'value']);
            });

        $result = $this->configProvider->getConfig($className, $version, $requestType, [$extra, $sectionExtra]);
        self::assertEquals('Test\Class|definition:test', $result->getDefinition()->getKey());
        self::assertTrue($definition->hasField('test_field'));
        self::assertEquals(['test_section_key' => 'value'], $result->get('test_section_extra'));
        self::assertNotSame($definition, $result->getDefinition());

        // test that the config is cached
        $anotherResult = $this->configProvider->getConfig($className, $version, $requestType, [$extra, $sectionExtra]);
        self::assertNotSame($result, $anotherResult);
        self::assertEquals($result, $anotherResult);
    }

    public function testShouldBuildConfigButNotCacheConfigAndNotSetConfigKeyForNotIdentifierFieldsOnlyConfig()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);

        $extra = new EntityDefinitionConfigExtra('test');

        $sectionExtra = $this->createMock(ConfigExtraSectionInterface::class);
        $sectionExtra->expects(self::any())
            ->method('getName')
            ->willReturn('test_section_extra');
        $sectionExtra->expects(self::any())
            ->method('getCacheKeyPart')
            ->willReturn('test_section_extra_key');

        $context = new ConfigContext();
        $definition = new EntityDefinitionConfig();

        $this->processor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::exactly(2))
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
                self::assertTrue($context->has(FilterIdentifierFieldsConfigExtra::NAME));
                self::assertFalse($context->get(FilterIdentifierFieldsConfigExtra::NAME));
                $extras = $context->getExtras();
                self::assertCount(2, $extras);
                self::assertSame($extra, $extras[0]);
                self::assertSame($sectionExtra, $extras[1]);

                $definition->addField('test_field');
                $context->setResult($definition);
                $context->set('test_section_extra', ['test_section_key' => 'value']);
            });

        $this->configProvider->disableFullConfigsCache();
        $result = $this->configProvider->getConfig($className, $version, $requestType, [$extra, $sectionExtra]);
        self::assertNull($result->getDefinition()->getKey());
        self::assertTrue($definition->hasField('test_field'));
        self::assertEquals(['test_section_key' => 'value'], $result->get('test_section_extra'));
        self::assertSame($definition, $result->getDefinition());

        // test that the config is not cached
        $anotherResult = $this->configProvider->getConfig($className, $version, $requestType, [$extra, $sectionExtra]);
        self::assertNotSame($result, $anotherResult);
        self::assertEquals($result, $anotherResult);
    }

    public function testShouldBuildConfigAndCacheConfigAndSetConfigKeyForIdentifierFieldsOnlyConfig()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);

        $extra = new EntityDefinitionConfigExtra('test');
        $identifierFieldsOnlyExtra = new FilterIdentifierFieldsConfigExtra();

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
                $identifierFieldsOnlyExtra,
                $sectionExtra,
                $definition
            ) {
                self::assertEquals($className, $context->getClassName());
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType->toArray(), $context->getRequestType()->toArray());
                self::assertTrue($context->has(FilterIdentifierFieldsConfigExtra::NAME));
                self::assertTrue($context->get(FilterIdentifierFieldsConfigExtra::NAME));
                $extras = $context->getExtras();
                self::assertCount(3, $extras);
                self::assertSame($extra, $extras[0]);
                self::assertSame($identifierFieldsOnlyExtra, $extras[1]);
                self::assertSame($sectionExtra, $extras[2]);

                $definition->addField('test_field');
                $context->setResult($definition);
                $context->set('test_section_extra', ['test_section_key' => 'value']);
            });

        $result = $this->configProvider->getConfig(
            $className,
            $version,
            $requestType,
            [$extra, $identifierFieldsOnlyExtra, $sectionExtra]
        );
        self::assertEquals('Test\Class|definition:test|identifier_fields_only', $result->getDefinition()->getKey());
        self::assertTrue($definition->hasField('test_field'));
        self::assertEquals(['test_section_key' => 'value'], $result->get('test_section_extra'));
        // a clone of definition should be returned
        self::assertNotSame($definition, $result->getDefinition());
        self::assertEquals($definition, $result->getDefinition());

        // test that the config is cached, but its clone should be returned
        $anotherResult = $this->configProvider->getConfig(
            $className,
            $version,
            $requestType,
            [$extra, $identifierFieldsOnlyExtra, $sectionExtra]
        );
        self::assertNotSame($result, $anotherResult);
        self::assertEquals($result, $anotherResult);
    }

    public function testShouldBePossibleToClearInternalCache()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);
        $extras = [new EntityDefinitionConfigExtra()];
        $context = new ConfigContext();

        $this->processor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::exactly(2))
            ->method('process')
            ->with(self::identicalTo($context));

        $this->configProvider->getConfig($className, $version, $requestType, $extras);

        $this->configProvider->reset();
        $this->configProvider->getConfig($className, $version, $requestType, $extras);
    }

    public function testShouldThrowExceptionIfCircularDependencyDetected()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot build the configuration of "Test\Class" because this causes the circular dependency.'
        );

        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);
        $extras = [new EntityDefinitionConfigExtra()];

        $context = new ConfigContext();

        $this->processor->expects(self::once())
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ConfigContext $context) use ($className, $version, $requestType, $extras) {
                $this->configProvider->getConfig($className, $version, $requestType, $extras);
            });

        $this->configProvider->getConfig($className, $version, $requestType, $extras);
    }
}

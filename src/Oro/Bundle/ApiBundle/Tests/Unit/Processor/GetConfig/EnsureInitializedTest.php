<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\EnsureInitialized;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EnsureInitializedTest extends ConfigProcessorTestCase
{
    private EnsureInitialized $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new EnsureInitialized($this->configLoaderFactory);
    }

    public function testProcessForNotInitializedConfigs(): void
    {
        $this->context->setExtras([
            new TestConfigSection('test_section'),
            new TestConfigExtra('test')
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            ['resource_class' => 'Test\Class'],
            $this->context->getResult()
        );
        $this->assertConfig(
            [],
            $this->context->getConfigSection('test_section')
        );
        self::assertFalse(
            $this->context->hasConfigSection('test')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->getRequestedExclusionPolicy());
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());
    }

    public function testProcessForAlreadyInitializedConfigs(): void
    {
        $definition = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => ['exclude' => true]
            ]
        ];
        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setConfigSection('test_section', $this->createConfigObject(['attr' => 'val']));
        $this->context->setExtras([
            new TestConfigSection('test_section'),
            new TestConfigExtra('test')
        ]);
        $this->processor->process($this->context);

        $resultDefinition = $definition;
        $resultDefinition['resource_class'] = 'Test\Class';
        $this->assertConfig(
            $resultDefinition,
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'attr' => 'val'
            ],
            $this->context->getConfigSection('test_section')
        );
        self::assertFalse(
            $this->context->hasConfigSection('test')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_ALL, $this->context->getRequestedExclusionPolicy());
        self::assertSame(['field1', 'field2'], $this->context->getExplicitlyConfiguredFieldNames());
    }

    public function testProcessForDisabledSorting(): void
    {
        $definition = [
            'disable_sorting' => true
        ];
        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setExtras([
            new SortersConfigExtra(),
            new TestConfigSection('test_section')
        ]);
        $this->processor->process($this->context);

        $resultDefinition = $definition;
        $resultDefinition['resource_class'] = 'Test\Class';
        $this->assertConfig(
            $resultDefinition,
            $this->context->getResult()
        );
        self::assertFalse($this->context->hasExtra(SortersConfigExtra::NAME));
        self::assertFalse($this->context->hasConfigSection(SortersConfigExtra::NAME));
        $this->assertConfig(
            [],
            $this->context->getConfigSection('test_section')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->getRequestedExclusionPolicy());
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());
    }

    public function testProcessForEnabledSorting(): void
    {
        $definition = [];
        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setExtras([
            new SortersConfigExtra(),
            new TestConfigSection('test_section')
        ]);
        $this->processor->process($this->context);

        $resultDefinition = $definition;
        $resultDefinition['resource_class'] = 'Test\Class';
        $this->assertConfig(
            $resultDefinition,
            $this->context->getResult()
        );
        self::assertTrue($this->context->hasExtra(SortersConfigExtra::NAME));
        $this->assertConfig(
            [],
            $this->context->getConfigSection(SortersConfigExtra::NAME)
        );
        $this->assertConfig(
            [],
            $this->context->getConfigSection('test_section')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->getRequestedExclusionPolicy());
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());
    }
}

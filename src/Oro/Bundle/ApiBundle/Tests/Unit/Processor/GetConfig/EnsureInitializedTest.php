<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\EnsureInitialized;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EnsureInitializedTest extends ConfigProcessorTestCase
{
    /** @var EnsureInitialized */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new EnsureInitialized($this->configLoaderFactory);
    }

    public function testProcessForNotInitializedConfigs()
    {
        $this->context->setExtras([
            new TestConfigSection('test_section'),
            new TestConfigExtra('test')
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [],
            $this->context->getResult()
        );
        $this->assertConfig(
            [],
            $this->context->get('test_section')
        );
        self::assertFalse(
            $this->context->has('test')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->getRequestedExclusionPolicy());
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());
    }

    public function testProcessForAlreadyInitializedConfigs()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => ['exclude' => true]
            ]
        ];
        $this->context->setResult($this->createConfigObject($config));
        $this->context->set('test_section', $this->createConfigObject(['attr' => 'val']));
        $this->context->setExtras([
            new TestConfigSection('test_section'),
            new TestConfigExtra('test')
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'attr' => 'val'
            ],
            $this->context->get('test_section')
        );
        self::assertFalse(
            $this->context->has('test')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_ALL, $this->context->getRequestedExclusionPolicy());
        self::assertSame(['field1', 'field2'], $this->context->getExplicitlyConfiguredFieldNames());
    }

    public function testProcessForDisabledSorting()
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

        $this->assertConfig(
            $definition,
            $this->context->getResult()
        );
        self::assertFalse($this->context->hasExtra(SortersConfigExtra::NAME));
        self::assertFalse($this->context->has(SortersConfigExtra::NAME));
        $this->assertConfig(
            [],
            $this->context->get('test_section')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->getRequestedExclusionPolicy());
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());
    }

    public function testProcessForEnabledSorting()
    {
        $definition = [];
        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setExtras([
            new SortersConfigExtra(),
            new TestConfigSection('test_section')
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            $definition,
            $this->context->getResult()
        );
        self::assertTrue($this->context->hasExtra(SortersConfigExtra::NAME));
        $this->assertConfig(
            [],
            $this->context->get(SortersConfigExtra::NAME)
        );
        $this->assertConfig(
            [],
            $this->context->get('test_section')
        );
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_NONE, $this->context->getRequestedExclusionPolicy());
        self::assertSame([], $this->context->getExplicitlyConfiguredFieldNames());
    }
}

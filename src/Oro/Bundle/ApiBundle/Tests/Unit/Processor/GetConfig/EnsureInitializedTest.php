<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\EnsureInitialized;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub\TestConfigExtension;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class EnsureInitializedTest extends ConfigProcessorTestCase
{
    /** @var EnsureInitialized */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configExtensionRegistry->addExtension(new TestConfigExtension());

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
    }

    public function testProcessForAlreadyInitializedConfigs()
    {
        $this->context->setResult($this->createConfigObject(['exclusion_policy' => 'all']));
        $this->context->set('test_section', $this->createConfigObject(['attr' => 'val']));
        $this->context->setExtras([
            new TestConfigSection('test_section'),
            new TestConfigExtra('test')
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
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
    }
}

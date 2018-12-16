<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\ExcludeCustomFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\EnsureInitialized;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub\TestConfigExtension;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class EnsureInitializedTest extends ConfigProcessorTestCase
{
    /** @var EnsureInitialized */
    private $processor;

    protected function setUp()
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

    public function testProcessWithExcludeCustomFieldsConfigExtra()
    {
        $this->context->setResult($this->createConfigObject([]));
        $this->context->setExtras([
            new ExcludeCustomFieldsConfigExtra()
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'custom_fields'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithExcludeCustomFieldsConfigExtraButExcludeWasNotRequested()
    {
        $this->context->setResult($this->createConfigObject([]));
        $this->context->setExtras([
            new ExcludeCustomFieldsConfigExtra(false)
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [],
            $this->context->getResult()
        );
    }

    public function testProcessWithExcludeCustomFieldsConfigExtraButExclusionPolicyIsAlreadySetToAll()
    {
        $this->context->setResult($this->createConfigObject(['exclusion_policy' => 'all']));
        $this->context->setExtras([
            new ExcludeCustomFieldsConfigExtra()
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithExcludeCustomFieldsConfigExtraButExclusionPolicyIsAlreadySetToNone()
    {
        $this->context->setResult($this->createConfigObject(['exclusion_policy' => 'none']));
        $this->context->setExtras([
            new ExcludeCustomFieldsConfigExtra()
        ]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [],
            $this->context->getResult()
        );
    }
}

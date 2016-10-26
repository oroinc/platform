<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\KeyObjectCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ContextConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\ContextMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\Shared\InitializeApiFormExtension;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class InitializeApiFormExtensionTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formExtensionSwitcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataTypeGuesser;

    /** @var InitializeApiFormExtension */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->formExtensionSwitcher = $this->getMock(FormExtensionSwitcherInterface::class);
        $this->metadataTypeGuesser = $this->getMockBuilder(MetadataTypeGuesser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new InitializeApiFormExtension(
            $this->formExtensionSwitcher,
            $this->metadataTypeGuesser
        );
    }

    public function testProcessWhenApiFormExtensionIsNotActivated()
    {
        $this->formExtensionSwitcher->expects($this->once())
            ->method('switchToApiFormExtension');
        $this->metadataTypeGuesser->expects($this->once())
            ->method('getIncludedObjects')
            ->willReturn(null);
        $this->metadataTypeGuesser->expects($this->once())
            ->method('getMetadataAccessor')
            ->willReturn(null);
        $this->metadataTypeGuesser->expects($this->once())
            ->method('getConfigAccessor')
            ->willReturn(null);
        $this->metadataTypeGuesser->expects($this->once())
            ->method('setIncludedObjects')
            ->with(null);
        $this->metadataTypeGuesser->expects($this->once())
            ->method('setMetadataAccessor')
            ->with($this->isInstanceOf(ContextMetadataAccessor::class));
        $this->metadataTypeGuesser->expects($this->once())
            ->method('setConfigAccessor')
            ->with($this->isInstanceOf(ContextConfigAccessor::class));

        $this->processor->process($this->context);

        self::assertFalse($this->context->has(InitializeApiFormExtension::PREVIOUS_INCLUDED_OBJECTS));
        self::assertFalse($this->context->has(InitializeApiFormExtension::PREVIOUS_METADATA_ACCESSOR));
        self::assertFalse($this->context->has(InitializeApiFormExtension::PREVIOUS_CONFIG_ACCESSOR));
    }

    public function testProcessWhenApiFormExtensionIsActivated()
    {
        $this->formExtensionSwitcher->expects($this->never())
            ->method('switchToApiFormExtension');
        $this->metadataTypeGuesser->expects($this->never())
            ->method('getIncludedObjects');
        $this->metadataTypeGuesser->expects($this->never())
            ->method('getMetadataAccessor');
        $this->metadataTypeGuesser->expects($this->never())
            ->method('getConfigAccessor');
        $this->metadataTypeGuesser->expects($this->never())
            ->method('setIncludedObjects');
        $this->metadataTypeGuesser->expects($this->never())
            ->method('setMetadataAccessor');
        $this->metadataTypeGuesser->expects($this->never())
            ->method('setConfigAccessor');

        $this->context->set(InitializeApiFormExtension::API_FORM_EXTENSION_ACTIVATED, true);
        $this->processor->process($this->context);
    }

    public function testProcessWhenMetadataTypeGuesserHasContext()
    {
        $currentIncludedObjects = $this->getMock(KeyObjectCollection::class);

        $includedObjects = $this->getMock(KeyObjectCollection::class);
        $metadataAccessor = $this->getMock(MetadataAccessorInterface::class);
        $configAccessor = $this->getMock(ConfigAccessorInterface::class);

        $this->formExtensionSwitcher->expects($this->once())
            ->method('switchToApiFormExtension');
        $this->metadataTypeGuesser->expects($this->once())
            ->method('getIncludedObjects')
            ->willReturn($includedObjects);
        $this->metadataTypeGuesser->expects($this->once())
            ->method('getMetadataAccessor')
            ->willReturn($metadataAccessor);
        $this->metadataTypeGuesser->expects($this->once())
            ->method('getConfigAccessor')
            ->willReturn($configAccessor);
        $this->metadataTypeGuesser->expects($this->once())
            ->method('setIncludedObjects')
            ->with($this->identicalTo($currentIncludedObjects));
        $this->metadataTypeGuesser->expects($this->once())
            ->method('setMetadataAccessor')
            ->with($this->isInstanceOf(ContextMetadataAccessor::class));
        $this->metadataTypeGuesser->expects($this->once())
            ->method('setConfigAccessor')
            ->with($this->isInstanceOf(ContextConfigAccessor::class));

        $this->context->setIncludedObjects($currentIncludedObjects);
        $this->processor->process($this->context);

        self::assertSame(
            $includedObjects,
            $this->context->get(InitializeApiFormExtension::PREVIOUS_INCLUDED_OBJECTS)
        );
        self::assertSame(
            $metadataAccessor,
            $this->context->get(InitializeApiFormExtension::PREVIOUS_METADATA_ACCESSOR)
        );
        self::assertSame(
            $configAccessor,
            $this->context->get(InitializeApiFormExtension::PREVIOUS_CONFIG_ACCESSOR)
        );
    }
}

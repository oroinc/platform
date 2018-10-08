<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\RestoreDefaultFormExtension;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class RestoreDefaultFormExtensionTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormExtensionSwitcherInterface */
    private $formExtensionSwitcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataTypeGuesser */
    private $metadataTypeGuesser;

    /** @var RestoreDefaultFormExtension */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->formExtensionSwitcher = $this->createMock(FormExtensionSwitcherInterface::class);
        $this->metadataTypeGuesser = $this->createMock(MetadataTypeGuesser::class);

        $this->processor = new RestoreDefaultFormExtension(
            $this->formExtensionSwitcher,
            $this->metadataTypeGuesser
        );
    }

    public function testProcessWhenApiFormExtensionIsNotActivated()
    {
        $this->formExtensionSwitcher->expects(self::never())
            ->method('switchToDefaultFormExtension');
        $this->metadataTypeGuesser->expects(self::never())
            ->method('setIncludedEntities')
            ->with(null);
        $this->metadataTypeGuesser->expects(self::never())
            ->method('setMetadataAccessor')
            ->with(null);
        $this->metadataTypeGuesser->expects(self::never())
            ->method('setConfigAccessor')
            ->with(null);

        $this->processor->process($this->context);
    }

    public function testProcessWhenApiFormExtensionIsActivated()
    {
        $this->formExtensionSwitcher->expects(self::once())
            ->method('switchToDefaultFormExtension');
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setIncludedEntities')
            ->with(null);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setMetadataAccessor')
            ->with(null);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setConfigAccessor')
            ->with(null);

        $this->context->set(RestoreDefaultFormExtension::API_FORM_EXTENSION_ACTIVATED, true);
        $this->processor->process($this->context);
    }

    public function testProcessForPreviouslyRememberedContext()
    {
        $includedEntities = $this->createMock(IncludedEntityCollection::class);
        $metadataAccessor = $this->createMock(MetadataAccessorInterface::class);
        $configAccessor = $this->createMock(ConfigAccessorInterface::class);

        $this->formExtensionSwitcher->expects(self::once())
            ->method('switchToDefaultFormExtension');
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setIncludedEntities')
            ->with($includedEntities);
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setMetadataAccessor')
            ->with(self::identicalTo($metadataAccessor));
        $this->metadataTypeGuesser->expects(self::once())
            ->method('setConfigAccessor')
            ->with(self::identicalTo($configAccessor));

        $this->context->set(RestoreDefaultFormExtension::API_FORM_EXTENSION_ACTIVATED, true);
        $this->context->set(RestoreDefaultFormExtension::PREVIOUS_INCLUDED_ENTITIES, $includedEntities);
        $this->context->set(RestoreDefaultFormExtension::PREVIOUS_METADATA_ACCESSOR, $metadataAccessor);
        $this->context->set(RestoreDefaultFormExtension::PREVIOUS_CONFIG_ACCESSOR, $configAccessor);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(RestoreDefaultFormExtension::PREVIOUS_INCLUDED_ENTITIES));
        self::assertFalse($this->context->has(RestoreDefaultFormExtension::PREVIOUS_METADATA_ACCESSOR));
        self::assertFalse($this->context->has(RestoreDefaultFormExtension::PREVIOUS_CONFIG_ACCESSOR));
    }
}

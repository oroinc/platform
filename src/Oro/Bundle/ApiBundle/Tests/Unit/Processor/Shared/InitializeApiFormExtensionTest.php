<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

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

        $this->formExtensionSwitcher = $this->getMock('Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface');
        $this->metadataTypeGuesser = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new InitializeApiFormExtension(
            $this->formExtensionSwitcher,
            $this->metadataTypeGuesser
        );
    }

    public function testProcess()
    {
        $this->formExtensionSwitcher->expects($this->once())
            ->method('switchToApiFormExtension');
        $this->metadataTypeGuesser->expects($this->once())
            ->method('setMetadataAccessor')
            ->with($this->isInstanceOf('Oro\Bundle\ApiBundle\Processor\ContextMetadataAccessor'));

        $this->processor->process($this->context);
    }
}

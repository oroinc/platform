<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ApiBundle\Processor\Shared\AddFormEventSubscriber;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class AddFormEventSubscriberTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EventSubscriberInterface */
    private $eventSubscriber;

    /** @var AddFormEventSubscriber */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->eventSubscriber = $this->createMock(EventSubscriberInterface::class);

        $this->processor = new AddFormEventSubscriber($this->eventSubscriber);
    }

    public function testProcessWhenFormBuilderDoesNotExistInContext()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenFormBuilderExistsInContext()
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $formBuilder->expects(self::once())
            ->method('addEventSubscriber')
            ->with(self::identicalTo($this->eventSubscriber));

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
    }
}

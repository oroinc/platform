<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\EventListener\CreateListener;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class BuildFormBuilderTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $propertyAccessor;

    /** @var BuildFormBuilder */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->getMock(FormFactoryInterface::class);
        $this->propertyAccessor = $this->getMock(PropertyAccessorInterface::class);

        $this->processor = new BuildFormBuilder($this->formFactory, $this->propertyAccessor);
    }

    public function testProcess()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->getMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                'form',
                $data,
                [
                    'data_class'           => $entityClass,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                    'api_context'          => $this->context
                ]
            )
            ->willReturn($formBuilder);
        $formBuilder->expects(self::once())
            ->method('addEventSubscriber')
            ->with(self::isInstanceOf(CreateListener::class));

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }
}

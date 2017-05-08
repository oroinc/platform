<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\EventListener\CreateListener;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class BuildFormBuilderTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var BuildFormBuilder */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->processor = new BuildFormBuilder(new FormHelper($this->formFactory));
    }

    public function testProcess()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

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
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
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

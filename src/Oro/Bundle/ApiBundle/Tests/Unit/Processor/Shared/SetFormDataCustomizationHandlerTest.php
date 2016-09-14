<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\SetFormDataCustomizationHandler;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameContainerType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetFormDataCustomizationHandlerTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $customizationProcessor;

    /** @var SetFormDataCustomizationHandler */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->customizationProcessor = $this->getMock('Oro\Component\ChainProcessor\ActionProcessorInterface');

        $this->processor = new SetFormDataCustomizationHandler($this->customizationProcessor);
    }

    public function testProcessWhenFormAlreadyBuilt()
    {
        $this->context->setForm($this->getMock('Symfony\Component\Form\Test\FormInterface'));
        $this->processor->process($this->context);
    }

    public function testProcessWhenFormBuilderDoesNotExist()
    {
        $this->processor->process($this->context);
    }

    public function testProcessForFormWithoutDataClass()
    {
        $formBuilder = $this->createFormBuilder();

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $formBuilder = new FormBuilder(
            null,
            Entity\User::class,
            new EventDispatcher(),
            Forms::createFormFactoryBuilder()->getFormFactory()
        );
        $formBuilder->setCompound(true);
        $formBuilder->setDataMapper(new PropertyPathMapper(PropertyAccess::createPropertyAccessor()));
        $formBuilder->add('name');
        $formBuilder->add(
            'owner',
            NameContainerType::class,
            ['data_class' => Entity\User::class]
        );
        $formBuilder->add(
            'groups',
            'collection',
            [
                'entry_type'    => NameContainerType::class,
                'entry_options' => ['data_class' => Entity\Group::class]
            ]
        );

        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $data = new Entity\User();
        $data->setName('oldName');
        $owner = new Entity\User();
        $owner->setName('oldOwner');
        $data->setOwner($owner);
        $group1 = new Entity\Group();
        $group1->setName('group1');
        $data->addGroup($group1);

        $form = $formBuilder->getForm();
        $form->setData($data);

        $expectedRootContext = $this->createCustomizeFormDataContext($this->context);
        $expectedRootContext->setConfig($this->context->getConfig());
        $expectedRootContext->setForm($form);
        $expectedRootContext->setClassName(Entity\User::class);
        $expectedRootContext->setResult($data);

        $expectedOwnerContext = $this->createCustomizeFormDataContext($this->context);
        $expectedOwnerContext->setConfig($this->context->getConfig());
        $expectedOwnerContext->setForm($form->get('owner'));
        $expectedOwnerContext->setRootClassName(Entity\User::class);
        $expectedOwnerContext->setClassName(Entity\User::class);
        $expectedOwnerContext->setPropertyPath('owner');
        $expectedOwnerContext->setResult($owner);

        $expectedGroup1Context = $this->createCustomizeFormDataContext($this->context);
        $expectedGroup1Context->setConfig($this->context->getConfig());
        $expectedGroup1Context->setForm($form->get('groups')->get(0));
        $expectedGroup1Context->setRootClassName(Entity\User::class);
        $expectedGroup1Context->setClassName(Entity\Group::class);
        $expectedGroup1Context->setPropertyPath('groups');
        $expectedGroup1Context->setResult($group1);

        $this->customizationProcessor->expects($this->any())
            ->method('createContext')
            ->willReturnCallback(
                function () {
                    return $this->createCustomizeFormDataContext($this->context);
                }
            );
        $this->customizationProcessor->expects($this->at(1))
            ->method('process')
            ->with($expectedGroup1Context);
        $this->customizationProcessor->expects($this->at(3))
            ->method('process')
            ->with($expectedOwnerContext);
        $this->customizationProcessor->expects($this->at(5))
            ->method('process')
            ->with($expectedRootContext);

        $form->submit([]);
    }

    /**
     * @param FormContext $context
     *
     * @return CustomizeFormDataContext
     */
    protected function createCustomizeFormDataContext(FormContext $context)
    {
        $result = new CustomizeFormDataContext();
        $result->setVersion($context->getVersion());
        $result->getRequestType()->set($context->getRequestType());

        return $result;
    }
}

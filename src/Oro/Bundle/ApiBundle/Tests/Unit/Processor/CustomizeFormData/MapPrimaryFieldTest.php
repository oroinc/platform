<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Extension\CustomizeFormDataExtension;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\MapPrimaryField;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameContainerType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\RenamedNameContainerType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\RestrictedNameContainerType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;

class MapPrimaryFieldTest extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $customizationProcessor;

    /** @var FormContext */
    protected $formContext;

    /** @var MapPrimaryField */
    protected $processor;

    protected function setUp()
    {
        $this->customizationProcessor = $this->getMock(ActionProcessorInterface::class);

        parent::setUp();

        $this->dispatcher = new EventDispatcher();
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);

        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formContext = new FormContextStub($configProvider, $metadataProvider);
        $this->formContext->setVersion('1.1');
        $this->formContext->getRequestType()->add(RequestType::REST);

        $this->processor = new MapPrimaryField(
            PropertyAccess::createPropertyAccessor(),
            'Unknown enabled group.',
            'enabledRole',
            'roles',
            'name',
            'enabled'
        );

        $this->customizationProcessor->expects($this->any())
            ->method('createContext')
            ->willReturnCallback(
                function () {
                    return new CustomizeFormDataContext();
                }
            );
        $this->customizationProcessor->expects($this->any())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeFormDataContext $context) {
                    if (Entity\Account::class === $context->getClassName()) {
                        $this->processor->process($context);
                    }
                }
            );
    }

    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension(
                [],
                [FormType::class => [new CustomizeFormDataExtension($this->customizationProcessor)]]
            )
        ];
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return FormBuilderInterface
     */
    protected function getFormBuilder(EntityDefinitionConfig $config)
    {
        $this->formContext->setConfig($config);

        return $this->builder->create(
            null,
            FormType::class,
            [
                'data_class'                            => Entity\Account::class,
                CustomizeFormDataExtension::API_CONTEXT => $this->formContext
            ]
        );
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param Entity\Account         $data
     * @param array                  $submittedData
     * @param array                  $itemOptions
     * @param string                 $entryType
     *
     * @return FormInterface
     */
    protected function processForm(
        EntityDefinitionConfig $config,
        Entity\Account $data,
        array $submittedData,
        array $itemOptions = [],
        $entryType = NameContainerType::class
    ) {
        $formBuilder = $this->getFormBuilder($config);
        $formBuilder->add('enabledRole', null, array_merge(['mapped' => false], $itemOptions));
        $formBuilder->add(
            'roles',
            'collection',
            [
                'by_reference'  => false,
                'allow_add'     => true,
                'allow_delete'  => true,
                'entry_type'    => $entryType,
                'entry_options' => array_merge(['data_class' => Entity\Role::class], $itemOptions)
            ]
        );

        $form = $formBuilder->getForm();
        $form->setData($data);
        $form->submit($submittedData, false);

        return $form;
    }

    /**
     * @param Entity\Account $data
     * @param string         $name
     * @param bool           $enabled
     *
     * @return Entity\Role
     */
    protected function addRole(Entity\Account $data, $name, $enabled)
    {
        $role = new Entity\Role();
        $role->setName($name);
        $role->setEnabled($enabled);
        $data->addRole($role);

        return $role;
    }

    public function testProcessWhenPrimaryFieldIsNotSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm($config, $data, []);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertFalse($role1->isEnabled());
        $this->assertTrue($role2->isEnabled());
    }

    public function testProcessWhenPrimaryFieldIsNotSubmittedButAssociationIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            $config,
            $data,
            [
                'roles' => [
                    ['name' => 'role1'],
                    ['name' => 'role2'],
                ]
            ]
        );
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertFalse($role1->isEnabled());
        $this->assertTrue($role2->isEnabled());
    }

    public function testProcessWhenEmptyValueForPrimaryFieldIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm($config, $data, ['enabledRole' => '']);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertFalse($role1->isEnabled());
        $this->assertFalse($role2->isEnabled());
    }

    public function testProcessWhenEmptyValueForPrimaryFieldIsSubmittedAndAssociationIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            $config,
            $data,
            [
                'enabledRole' => '',
                'roles'       => [
                    ['name' => 'role1'],
                    ['name' => 'role2'],
                ]
            ]
        );
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertFalse($role1->isEnabled());
        $this->assertFalse($role2->isEnabled());
    }

    public function testProcessWhenPrimaryFieldIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm($config, $data, ['enabledRole' => 'role1']);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertTrue($role1->isEnabled());
        $this->assertFalse($role2->isEnabled());
    }

    public function testProcessWhenBothPrimaryFieldAndAssociationAreSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            $config,
            $data,
            [
                'enabledRole' => 'role1',
                'roles'       => [
                    ['name' => 'role1'],
                    ['name' => 'role2'],
                ]
            ]
        );
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertTrue($role1->isEnabled());
        $this->assertFalse($role2->isEnabled());
    }

    public function testProcessWhenUnknownValueForPrimaryFieldIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm($config, $data, ['enabledRole' => 'unknown']);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $roles = $data->getRoles();
        $this->assertCount(3, $roles);
        $this->assertFalse($role1->isEnabled());
        $this->assertFalse($role2->isEnabled());
        $this->assertTrue($roles[2]->isEnabled());
    }

    public function testProcessWhenUnknownValueForPrimaryFieldIsSubmittedAndAssociationIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $this->addRole($data, 'role1', false);
        $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            $config,
            $data,
            [
                'enabledRole' => 'unknown',
                'roles'       => [
                    ['name' => 'role1'],
                    ['name' => 'role2'],
                ]
            ]
        );
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        /** @var FormError[] $errors */
        $errors = $form->get('enabledRole')->getErrors();
        $this->assertEquals(
            'Unknown enabled group.',
            $errors[0]->getMessage()
        );
    }

    public function testProcessWhenInvalidValueForPrimaryFieldIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $this->addRole($data, 'role1', false);
        $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            $config,
            $data,
            ['enabledRole' => '1'],
            ['constraints' => [new Assert\Length(['min' => 3])]],
            RestrictedNameContainerType::class
        );
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        /** @var FormError[] $errors */
        $errors = $form->get('enabledRole')->getErrors();
        $this->assertEquals(
            'This value is too short. It should have 3 characters or more.',
            $errors[0]->getMessage()
        );
        $this->assertCount(0, $form->get('roles')->get('2')->getErrors(true));
    }

    public function testProcessWhenInvalidValueForPrimaryFieldIsSubmittedAndAssociationIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $this->addRole($data, 'role1', false);
        $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            $config,
            $data,
            [
                'enabledRole' => '1',
                'roles'       => [
                    ['name' => 'role1'],
                    ['name' => 'role2'],
                ]
            ],
            ['constraints' => [new Assert\Length(['min' => 3])]]
        );
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        /** @var FormError[] $errors */
        $errors = $form->get('enabledRole')->getErrors();
        $this->assertEquals(
            'Unknown enabled group.',
            $errors[0]->getMessage()
        );
    }

    public function testProcessForRenamedFields()
    {
        $this->processor = new MapPrimaryField(
            PropertyAccess::createPropertyAccessor(),
            'Unknown enabled group.',
            'enabledRole',
            'renamedRoles',
            'renamedName',
            'enabled'
        );

        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('renamedRoles');
        $rolesField->setPropertyPath('roles');
        $rolesField->getOrCreateTargetEntity()->addField('renamedName')->setPropertyPath('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $formBuilder = $this->getFormBuilder($config);
        $formBuilder->add('enabledRole', null, ['mapped' => false]);
        $formBuilder->add(
            'renamedRoles',
            'collection',
            [
                'property_path' => 'roles',
                'by_reference'  => false,
                'allow_add'     => true,
                'allow_delete'  => true,
                'entry_type'    => RenamedNameContainerType::class,
                'entry_options' => ['data_class' => Entity\Role::class]
            ]
        );

        $form = $formBuilder->getForm();
        $form->setData($data);
        $form->submit(['enabledRole' => 'role1'], false);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertTrue($role1->isEnabled());
        $this->assertFalse($role2->isEnabled());
    }
}

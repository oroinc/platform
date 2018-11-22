<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Extension\CustomizeFormDataExtension;
use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Oro\Bundle\ApiBundle\Form\FormValidationHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\MapPrimaryField;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameContainerType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\RenamedNameContainerType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\RestrictedNameContainerType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Constraints\Form as FormConstraint;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MapPrimaryFieldTest extends TypeTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorInterface */
    private $customizationProcessor;

    /** @var CustomizeFormDataHandler */
    private $customizationHandler;

    /** @var FormContext */
    private $formContext;

    /** @var ValidatorInterface */
    private $validator;

    /** @var FormValidationHandler */
    private $formValidationHandler;

    /** @var MapPrimaryField */
    private $processor;

    protected function setUp()
    {
        $this->customizationProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->customizationHandler = new CustomizeFormDataHandler($this->customizationProcessor);
        $this->validator = Validation::createValidator();
        /* @var ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor(Form::class);
        $metadata->addConstraint(new FormConstraint());
        $metadata->addPropertyConstraint('children', new Assert\Valid());

        parent::setUp();

        $this->dispatcher = new EventDispatcher();
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);

        $configProvider = $this->createMock(ConfigProvider::class);
        $metadataProvider = $this->createMock(MetadataProvider::class);
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

        $this->customizationProcessor->expects(self::any())
            ->method('createContext')
            ->willReturnCallback(
                function () {
                    return new CustomizeFormDataContext();
                }
            );
        $this->customizationProcessor->expects(self::any())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeFormDataContext $context) {
                    if (Entity\Account::class === $context->getClassName()) {
                        $this->processor->process($context);
                    }
                }
            );

        $this->formValidationHandler = new FormValidationHandler(
            $this->validator,
            $this->customizationHandler,
            new PropertyAccessor()
        );
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [],
                [
                    FormType::class => [
                        new ValidationExtension($this->validator),
                        new CustomizeFormDataExtension($this->customizationProcessor, $this->customizationHandler)
                    ]
                ]
            )
        ];
    }

    /**
     * @param EntityDefinitionConfig|null $config
     *
     * @return FormBuilderInterface
     */
    private function getFormBuilder(?EntityDefinitionConfig $config)
    {
        $this->formContext->setConfig($config);

        return $this->builder->create(
            null,
            FormType::class,
            [
                'data_class'                          => Entity\Account::class,
                'enable_validation'                   => false,
                CustomizeFormDataHandler::API_CONTEXT => $this->formContext
            ]
        );
    }

    /**
     * @param EntityDefinitionConfig|null $config
     * @param Entity\Account              $data
     * @param array                       $submittedData
     * @param array                       $itemOptions
     * @param string                      $entryType
     *
     * @return FormInterface
     */
    private function processForm(
        ?EntityDefinitionConfig $config,
        Entity\Account $data,
        array $submittedData,
        array $itemOptions = [],
        $entryType = NameContainerType::class
    ) {
        $formBuilder = $this->getFormBuilder($config);
        $formBuilder->add('enabledRole', null, array_merge(['mapped' => false], $itemOptions));
        $formBuilder->add(
            'roles',
            CollectionType::class,
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
        $this->formValidationHandler->validate($form);

        return $form;
    }

    /**
     * @param Entity\Account $data
     * @param string         $name
     * @param bool           $enabled
     *
     * @return Entity\Role
     */
    private function addRole(Entity\Account $data, $name, $enabled)
    {
        $role = new Entity\Role();
        $role->setName($name);
        $role->setEnabled($enabled);
        $data->addRole($role);

        return $role;
    }

    public function testProcessWithoutConfigShouldWorkAsRegularForm()
    {
        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            null,
            $data,
            [
                'enabledRole' => 'role1',
                'roles'       => [
                    ['name' => 'role1'],
                    ['name' => 'role2'],
                    ['name' => 'role3']
                ]
            ]
        );
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertTrue($role2->isEnabled());
        self::assertCount(3, $data->getRoles());
    }

    public function testProcessWithoutAssociationConfigShouldWorkAsRegularForm()
    {
        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm(
            new EntityDefinitionConfig(),
            $data,
            [
                'enabledRole' => 'role1',
                'roles'       => [
                    ['name' => 'role1'],
                    ['name' => 'role2']
                ]
            ]
        );
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertTrue($role2->isEnabled());
    }

    public function testProcessWithoutPrimaryFieldFormFieldShouldWorkAsRegularForm()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $formBuilder = $this->getFormBuilder($config);
        $formBuilder->add(
            'roles',
            CollectionType::class,
            [
                'by_reference'  => false,
                'allow_add'     => true,
                'allow_delete'  => true,
                'entry_type'    => NameContainerType::class,
                'entry_options' => ['data_class' => Entity\Role::class]
            ]
        );
        $form = $formBuilder->getForm();
        $form->setData($data);
        $form->submit(
            [
                'roles' => [
                    ['name' => 'role1'],
                    ['name' => 'role2'],
                    ['name' => 'role3']
                ]
            ],
            false
        );
        $this->formValidationHandler->validate($form);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertTrue($role2->isEnabled());
        self::assertCount(3, $data->getRoles());
    }

    public function testProcessWithoutAssociationFormFieldShouldWorkAsRegularForm()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $formBuilder = $this->getFormBuilder($config);
        $formBuilder->add('enabledRole', null, ['mapped' => false]);
        $form = $formBuilder->getForm();
        $form->setData($data);
        $form->submit(['enabledRole' => 'role1'], false);
        $this->formValidationHandler->validate($form);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertTrue($role2->isEnabled());
    }

    public function testProcessWhenPrimaryFieldAndAssociationAreNotSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm($config, $data, []);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertTrue($role2->isEnabled());
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
                    ['name' => 'role2']
                ]
            ]
        );
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertTrue($role2->isEnabled());
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertFalse($role2->isEnabled());
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
                    ['name' => 'role2']
                ]
            ]
        );
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertFalse($role1->isEnabled());
        self::assertFalse($role2->isEnabled());
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertTrue($role1->isEnabled());
        self::assertFalse($role2->isEnabled());
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
                    ['name' => 'role2']
                ]
            ]
        );
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertTrue($role1->isEnabled());
        self::assertFalse($role2->isEnabled());
    }

    public function testProcessWhenNewValueForPrimaryFieldIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $role1 = $this->addRole($data, 'role1', false);
        $role2 = $this->addRole($data, 'role2', true);

        $form = $this->processForm($config, $data, ['enabledRole' => 'role3']);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        $roles = $data->getRoles();
        self::assertCount(2, $roles);
        self::assertFalse($role1->isEnabled());
        self::assertTrue($role2->isEnabled());
        self::assertEquals('role3', $role2->getName());
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
                    ['name' => 'role2']
                ]
            ]
        );
        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        /** @var FormError[] $errors */
        $errors = $form->get('enabledRole')->getErrors();
        self::assertEquals(
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
        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        /** @var FormError[] $errors */
        $errors = $form->get('enabledRole')->getErrors();
        self::assertEquals(
            'This value is too short. It should have 3 characters or more.',
            $errors[0]->getMessage()
        );
        self::assertCount(0, $form->get('roles')->getErrors(true));
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
                    ['name' => 'role2']
                ]
            ],
            ['constraints' => [new Assert\Length(['min' => 3])]]
        );
        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        /** @var FormError[] $errors */
        $errors = $form->get('enabledRole')->getErrors();
        self::assertEquals(
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
            CollectionType::class,
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertTrue($role1->isEnabled());
        self::assertFalse($role2->isEnabled());
    }
}

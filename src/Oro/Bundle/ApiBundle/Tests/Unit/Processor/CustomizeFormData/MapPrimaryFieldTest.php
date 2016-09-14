<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\RenamedNameContainerType;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\MapPrimaryField;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameContainerType;

class MapPrimaryFieldTest extends TypeTestCase
{
    /** @var MapPrimaryField */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new MapPrimaryField(
            PropertyAccess::createPropertyAccessor(),
            'Unknown enabled group.',
            'enabledRole',
            'roles',
            'name',
            'enabled'
        );
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param Entity\Account         $data
     * @param array                  $submittedData
     *
     * @return FormInterface
     */
    protected function processForm(EntityDefinitionConfig $config, Entity\Account $data, array $submittedData)
    {
        $formBuilder = new FormBuilder(
            null,
            Entity\Account::class,
            new EventDispatcher(),
            $this->factory
        );
        $formBuilder->setCompound(true);
        $formBuilder->setDataMapper(new PropertyPathMapper(PropertyAccess::createPropertyAccessor()));
        $formBuilder->add('enabledRole', null, ['mapped' => false]);
        $formBuilder->add(
            'roles',
            'collection',
            [
                'entry_type'    => NameContainerType::class,
                'entry_options' => ['data_class' => Entity\Role::class]
            ]
        );
        $this->addFormListener($formBuilder, $config);

        $form = $formBuilder->getForm();
        $form->setData($data);
        $form->submit($submittedData, false);

        return $form;
    }

    /**
     * @param FormBuilder            $formBuilder
     * @param EntityDefinitionConfig $config
     */
    protected function addFormListener(FormBuilder $formBuilder, EntityDefinitionConfig $config)
    {
        $formBuilder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($config) {
                $context = new CustomizeFormDataContext();
                $context->setClassName(Entity\Account::class);
                $context->setConfig($config);
                $context->setForm($event->getForm());
                $context->setResult($event->getForm()->getViewData());
                $this->processor->process($context);
            }
        );
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

    public function testProcessWhenUnknownValueForPrimaryFieldIsSubmitted()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesField = $config->addField('roles');
        $rolesField->getOrCreateTargetEntity()->addField('name');

        $data = new Entity\Account();
        $this->addRole($data, 'role1', false);
        $this->addRole($data, 'role2', true);

        $form = $this->processForm($config, $data, ['enabledRole' => 'unknown']);
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

        $formBuilder = new FormBuilder(
            null,
            Entity\Account::class,
            new EventDispatcher(),
            $this->factory
        );
        $formBuilder->setCompound(true);
        $formBuilder->setDataMapper(new PropertyPathMapper(PropertyAccess::createPropertyAccessor()));
        $formBuilder->add('enabledRole', null, ['mapped' => false]);
        $formBuilder->add(
            'renamedRoles',
            'collection',
            [
                'property_path' => 'roles',
                'entry_type'    => RenamedNameContainerType::class,
                'entry_options' => ['data_class' => Entity\Role::class]
            ]
        );
        $this->addFormListener($formBuilder, $config);

        $form = $formBuilder->getForm();
        $form->setData($data);
        $form->submit(['enabledRole' => 'role1'], false);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertTrue($role1->isEnabled());
        $this->assertFalse($role2->isEnabled());
    }
}

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\SecurityBundle\Form\Extension\AclProtectedFieldTypeExtension;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class AclProtectedFieldTypeExtensionTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fieldAclHelper;

    /** @var TestLogger */
    protected $logger;

    /** @var AclProtectedFieldTypeExtension */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldAclHelper = $this->createMock(FieldAclHelper::class);
        $this->logger = new TestLogger();

        $this->extension = new AclProtectedFieldTypeExtension(
            $this->fieldAclHelper,
            $this->logger
        );
    }

    public function testGetExtendedType()
    {
        $expectedResult = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Symfony\Component\Form\Extension\Core\Type\FormType'
            : 'form';

        $this->assertEquals(
            $expectedResult,
            $this->extension->getExtendedType()
        );
    }

    public function testBuildFormWithCorrectData()
    {
        $options = $this->prepareCorrectOptions('Acme\Demo\TestEntity');
        list($dispatcher, $builder) = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, $options);
        $listeners = $dispatcher->getListeners();
        $this->assertCount(2, $listeners);
        $this->assertTrue(array_key_exists(FormEvents::PRE_SUBMIT, $listeners));
        $this->assertTrue(array_key_exists(FormEvents::POST_SUBMIT, $listeners));
    }

    public function testBuildFormWithoutDataClassInOptions()
    {
        $options = [];
        list($dispatcher, $builder) = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, $options);
        $listeners = $dispatcher->getListeners();
        $this->assertCount(0, $listeners);
    }

    public function testBuildFormWithNonSecurityProtectedSupportedClass()
    {
        $className = 'Acme\Demo\TestEntity';

        $this->fieldAclHelper->expects(self::once())
            ->method('isFieldAclEnabled')
            ->with($className)
            ->willReturn(false);
        $this->fieldAclHelper->expects(self::never())
            ->method('isRestrictedFieldsVisible');

        list($dispatcher, $builder) = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, ['data_class' => $className]);
        $listeners = $dispatcher->getListeners();
        $this->assertCount(0, $listeners);
    }

    public function testFinishView()
    {
        list($view, $form, $options) = $this->getTestFormAndFormView(true);
        $this->fieldAclHelper->expects(self::exactly(3))
            ->method('isFieldViewGranted')
            ->willReturn(false);
        $this->fieldAclHelper->expects(self::exactly(3))
            ->method('isFieldModificationGranted')
            ->willReturn(false);

        $this->extension->finishView($view, $form, $options);

        /** @var FormErrorIterator $formErrors */
        $formErrors = $view->vars['errors'];
        $this->assertEquals(1, $formErrors->count());
        $this->assertEquals(
            'The form contains fields "city, country" that are required or not valid but you have no access to them. '
            . 'Please contact your administrator to solve this issue.',
            $formErrors->current()->getMessage()
        );
        $this->assertEquals(2, $this->logger->countErrors());
        $this->assertEquals(
            "Non accessible field `city` detected in form `form`. Validation errors: ERROR: city error\n",
            $this->logger->getLogs('error')[0]
        );
        $this->assertEquals(
            "Non accessible field `country` detected in form `form`. Validation errors: ERROR: country error\n",
            $this->logger->getLogs('error')[1]
        );
    }

    public function testFinishViewWithShowRestricted()
    {
        list($view, $form, $options) = $this->getTestFormAndFormView(true);
        $this->fieldAclHelper->expects(self::exactly(3))
            ->method('isFieldViewGranted')
            ->willReturnCallback(
                function ($entity, $fieldName) {
                    return $fieldName !== 'city';
                }
            );
        $this->fieldAclHelper->expects(self::exactly(3))
            ->method('isFieldModificationGranted')
            ->willReturn(false);

        $this->extension->finishView($view, $form, $options);

        /** @var FormErrorIterator $formErrors */
        $formErrors = $view->vars['errors'];
        $this->assertEquals(1, $formErrors->count());
        $this->assertEquals(
            'The form contains fields "city" that are required or not valid but you have no access to them. '
            . 'Please contact your administrator to solve this issue.',
            $formErrors->current()->getMessage()
        );
        $this->assertEquals(1, $this->logger->countErrors());
        $this->assertEquals(
            "Non accessible field `city` detected in form `form`. Validation errors: ERROR: city error\n",
            $this->logger->getLogs('error')[0]
        );
        $this->assertTrue($view->children['city']->isRendered());
        $this->assertFalse($view->children['street']->isRendered());
        $this->assertTrue($view->children['street']->vars['attr']['readonly']);
        $this->assertFalse($view->children['country']->isRendered());
        $this->assertTrue($view->children['country']->vars['attr']['readonly']);
    }

    public function testPreSubmitOnEmptyData()
    {
        $data = [];
        $form = $this->factory->create(FormType::class, new CmsAddress(), []);
        $event = new FormEvent($form, $data);
        $this->extension->preSubmit($event);
        $this->assertCount(0, $event->getData());
    }

    public function testPreAndPostSubmit()
    {
        $options = $this->prepareCorrectOptions(CmsAddress::class);
        $entity = new CmsAddress();
        $entity->country = 'USA';
        $entity->city = 'Los Angeles';
        $entity->street = 'Main street';
        $entity->zip = 78945;
        /** @var Form $form */
        $form = $this->factory->create(FormType::class, $entity, $options);
        $form->add('city');
        $form->add('street');
        $form->add('country');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $builder = new FormBuilder('postoffice', null, $dispatcher, $this->factory);
        $builder->setPropertyPath(new PropertyPath('zip'));
        $builder->setAttribute('error_mapping', array());
        $builder->setErrorBubbling(false);
        $builder->setMapped(true);
        $form->add($builder->getForm());

        $data = [
            'country' => 'some country',
            'city' => 'some city',
            'street' => 'some street',
            'postoffice' => 61000
        ];

        $this->fieldAclHelper->expects(self::exactly(4))
            ->method('isFieldModificationGranted')
            ->willReturnCallback(
                function ($entity, $fieldName) {
                    return !in_array($fieldName, ['country', 'zip'], true);
                }
            );
        $this->fieldAclHelper->expects(self::exactly(2))
            ->method('addFieldModificationDeniedFormError')
            ->willReturnCallback(function (FormInterface $formField) {
                $this->addFieldModificationDeniedFormError($formField);
            });

        $event = new FormEvent($form, $data);
        $this->extension->preSubmit($event);

        $this->assertEquals(
            [
                'country' => 'USA',
                'city' => 'some city',
                'street' => 'some street',
                'postoffice' => '78945'
            ],
            $event->getData()
        );

        $postSubmitEvent = new FormEvent($form, $entity);
        $this->extension->postSubmit($postSubmitEvent);

        $countryErrors = $form->get('country')->getErrors();
        $this->assertCount(1, $countryErrors);
        $this->assertEquals(
            'You have no access to modify this field.',
            $countryErrors[0]->getMessage()
        );
        $postofficeErrors = $form->get('postoffice')->getErrors();
        $this->assertCount(1, $postofficeErrors);
        $this->assertEquals(
            'You have no access to modify this field.',
            $postofficeErrors[0]->getMessage()
        );
        $this->assertCount(0, $form->get('city')->getErrors());
        $this->assertCount(0, $form->get('street')->getErrors());
    }

    /**
     * @param bool $showRestricted
     *
     * @return array [view, form, options]
     */
    protected function getTestFormAndFormView($showRestricted = true)
    {
        $options = $this->prepareCorrectOptions(CmsAddress::class, $showRestricted);
        $view = new FormView();
        $view->children = ['city' => new FormView(), 'street' => new FormView(), 'country' => new FormView()];
        $form = $this->factory->create(FormType::class, new CmsAddress(), $options);
        $form->add('city');
        $form->add('street');
        $form->add('country');
        $form->get('city')->addError(new FormError('city error'));
        $form->get('country')->addError(new FormError('country error'));

        return [$view, $form, $options];
    }

    /**
     * @param string $className
     * @param bool   $showRestricted
     *
     * @return array
     */
    protected function prepareCorrectOptions($className, $showRestricted = true)
    {
        $this->fieldAclHelper->expects(self::any())
            ->method('isFieldAclEnabled')
            ->with($className)
            ->willReturn(true);
        $this->fieldAclHelper->expects(self::any())
            ->method('isRestrictedFieldsVisible')
            ->with($className)
            ->willReturn($showRestricted);

        return [
            'data_class' => $className,
        ];
    }

    /**
     * @return array [dispatcher, builder]
     */
    protected function getFormBuilderWithEventDispatcher($dataClass = null, $formName = null)
    {
        $dispatcher = new EventDispatcher();
        $formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()->getMock();
        $builder = new FormBuilder($formName, $dataClass, $dispatcher, $formFactory);

        return [$dispatcher, $builder];
    }

    /**
     * @param FormInterface $formField
     */
    protected function addFieldModificationDeniedFormError(FormInterface $formField)
    {
        $formField->addError(new FormError('You have no access to modify this field.'));
    }
}

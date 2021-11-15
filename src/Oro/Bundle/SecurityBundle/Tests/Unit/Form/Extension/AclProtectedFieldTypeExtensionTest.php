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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class AclProtectedFieldTypeExtensionTest extends FormIntegrationTestCase
{
    /** @var FieldAclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldAclHelper;

    /** @var TestLogger */
    private $logger;

    /** @var AclProtectedFieldTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldAclHelper = $this->createMock(FieldAclHelper::class);
        $this->logger = new TestLogger();

        $this->extension = new AclProtectedFieldTypeExtension(
            $this->fieldAclHelper,
            $this->logger
        );
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([FormType::class], AclProtectedFieldTypeExtension::getExtendedTypes());
    }

    public function testBuildFormWithCorrectData(): void
    {
        $options = $this->prepareCorrectOptions('Acme\Demo\TestEntity');
        [$dispatcher, $builder] = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, $options);
        $listeners = $dispatcher->getListeners();
        self::assertCount(2, $listeners);
        self::assertArrayHasKey(FormEvents::PRE_SUBMIT, $listeners);
        self::assertArrayHasKey(FormEvents::POST_SUBMIT, $listeners);
    }

    public function testBuildFormWithoutDataClassInOptions(): void
    {
        $options = [];
        [$dispatcher, $builder] = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, $options);
        $listeners = $dispatcher->getListeners();
        self::assertCount(0, $listeners);
    }

    public function testBuildFormWithNonSecurityProtectedSupportedClass(): void
    {
        $className = 'Acme\Demo\TestEntity';

        $this->fieldAclHelper->expects(self::once())
            ->method('isFieldAclEnabled')
            ->with($className)
            ->willReturn(false);
        $this->fieldAclHelper->expects(self::never())
            ->method('isRestrictedFieldsVisible');

        [$dispatcher, $builder] = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, ['data_class' => $className]);
        $listeners = $dispatcher->getListeners();
        self::assertCount(0, $listeners);
    }

    public function testFinishView(): void
    {
        /** @var FormView $view */
        [$view, $form, $options] = $this->getTestFormAndFormView();
        $view->children['broken'] = new \stdClass();

        $this->fieldAclHelper->expects(self::exactly(3))
            ->method('isFieldViewGranted')
            ->willReturn(false);
        $this->fieldAclHelper->expects(self::exactly(3))
            ->method('isFieldModificationGranted')
            ->willReturn(false);

        $this->extension->finishView($view, $form, $options);

        /** @var FormErrorIterator $formErrors */
        $formErrors = $view->vars['errors'];
        self::assertEquals(1, $formErrors->count());
        self::assertEquals(
            'The form contains fields "city, country" that are required or not valid but you have no access to them. '
            . 'Please contact your administrator to solve this issue.',
            $formErrors->current()->getMessage()
        );
        self::assertEquals(2, $this->logger->countErrors());
        self::assertTrue(
            $this->logger->hasRecord(
                "Non accessible field `city` detected in form `form`. Validation errors: ERROR: city error\n",
                'error'
            )
        );
        self::assertTrue(
            $this->logger->hasRecord(
                "Non accessible field `country` detected in form `form`. Validation errors: ERROR: country error\n",
                'error'
            )
        );
        self::assertFalse(isset($view->children['broken']));
    }

    public function testFinishViewWithShowRestricted(): void
    {
        [$view, $form, $options] = $this->getTestFormAndFormView();
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
        self::assertEquals(1, $formErrors->count());
        self::assertEquals(
            'The form contains fields "city" that are required or not valid but you have no access to them. '
            . 'Please contact your administrator to solve this issue.',
            $formErrors->current()->getMessage()
        );
        self::assertEquals(1, $this->logger->countErrors());
        self::assertTrue(
            $this->logger->hasRecord(
                "Non accessible field `city` detected in form `form`. Validation errors: ERROR: city error\n",
                'error'
            )
        );
        self::assertArrayNotHasKey('city', $view->children);
        self::assertFalse($view->children['street']->isRendered());
        self::assertTrue($view->children['street']->vars['attr']['readonly']);
        self::assertFalse($view->children['country']->isRendered());
        self::assertTrue($view->children['country']->vars['attr']['readonly']);
    }

    public function testPreSubmitOnEmptyData(): void
    {
        $data = [];
        $form = $this->factory->create(FormType::class, new CmsAddress(), []);
        $event = new FormEvent($form, $data);
        $this->extension->preSubmit($event);
        self::assertCount(0, $event->getData());
    }

    public function testPreAndPostSubmit(): void
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
        $form->add('zip');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $builder = new FormBuilder('postoffice', null, $dispatcher, $this->factory);
        $builder->setPropertyPath(new PropertyPath('zip'));
        $builder->setAttribute('error_mapping', []);
        $builder->setErrorBubbling(false);
        $builder->setMapped(true);
        $form->add($builder->getForm());

        $data = [
            'country' => 'some country',
            'city' => 'some city',
            'street' => 'some street',
            'postoffice' => 61000
        ];

        $this->fieldAclHelper->expects(self::exactly(5))
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

        self::assertFalse($form->has('zip'));

        self::assertEquals(
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
        self::assertCount(1, $countryErrors);
        self::assertEquals(
            'You have no access to modify this field.',
            $countryErrors[0]->getMessage()
        );
        $postofficeErrors = $form->get('postoffice')->getErrors();
        self::assertCount(1, $postofficeErrors);
        self::assertEquals(
            'You have no access to modify this field.',
            $postofficeErrors[0]->getMessage()
        );
        self::assertCount(0, $form->get('city')->getErrors());
        self::assertCount(0, $form->get('street')->getErrors());
    }

    /**
     * @param bool $showRestricted
     *
     * @return array [view, form, options]
     */
    private function getTestFormAndFormView(bool $showRestricted = true): array
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

    private function prepareCorrectOptions(string $className, bool $showRestricted = true): array
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
    private function getFormBuilderWithEventDispatcher(string $dataClass = null, string $formName = null): array
    {
        $dispatcher = new EventDispatcher();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $builder = new FormBuilder($formName, $dataClass, $dispatcher, $formFactory);

        return [$dispatcher, $builder];
    }

    private function addFieldModificationDeniedFormError(FormInterface $formField): void
    {
        $formField->addError(new FormError('You have no access to modify this field.'));
    }
}

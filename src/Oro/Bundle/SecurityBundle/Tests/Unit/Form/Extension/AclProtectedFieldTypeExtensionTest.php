<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Form\Extension\AclProtectedFieldTypeExtension;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class AclProtectedFieldTypeExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var FieldAclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldAclHelper;

    /** DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var AclProtectedFieldTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldAclHelper = $this->createMock(FieldAclHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->extension = new AclProtectedFieldTypeExtension($this->fieldAclHelper, $this->logger);
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
        self::assertCount(1, $listeners);
        self::assertArrayHasKey(FormEvents::PRE_SUBMIT, $listeners);
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
        [$view, $form, $options, $entity] = $this->getTestFormAndFormView();
        $view->children['broken'] = new \stdClass();

        $this->fieldAclHelper
            ->expects(self::exactly(3))
            ->method('isFieldModificationGranted')
            ->willReturn(true);

        $this->extension->finishView($view, $form, $options);

        $expectedView = new FormView();
        $expectedVars = ['attr' => ['readonly' => false], 'disabled' => false];
        $expectedView->vars = array_merge($expectedView->vars, $expectedVars);
        self::assertFalse(isset($view->children['broken']));
        self::assertEquals(
            ['city' => $expectedView, 'street' => $expectedView, 'country' => $expectedView],
            $view->children
        );
    }

    public function testFinishViewWithShowRestricted(): void
    {
        [$view, $form, $options, $entity] = $this->getTestFormAndFormView();
        $this->fieldAclHelper
            ->expects(self::exactly(3))
            ->method('isFieldModificationGranted')
            ->willReturn(false);

        $this->extension->finishView($view, $form, $options);

        $expectedView = new FormView();
        $expectedVars = ['attr' => ['readonly' => true], 'disabled' => true];
        $expectedView->vars = array_merge($expectedView->vars, $expectedVars);
        self::assertEquals(
            ['city' => $expectedView, 'street' => $expectedView, 'country' => $expectedView],
            $view->children
        );
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
        $entity = $this->getEntity(
            CmsAddress::class,
            [
                'id' => 1,
                'country' => 'USA',
                'city' => 'Los Angeles',
                'street' => 'Main street',
                'zip' => 78945
            ]
        );
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

        $this->fieldAclHelper
            ->expects(self::any())
            ->method('isFieldModificationGranted')
            ->willReturnMap([
                [$entity, 'city', true],
                [$entity, 'street', true],
                [$entity, 'country', false],
                [$entity, 'zip', false],
                [$entity, 'postoffice', true],
            ]);

        $event = new FormEvent($form, [
            'country' => 'some country',
            'zip' => 12345,
            'city' => 'some city',
            'street' => 'some street',
            'postoffice' => 61000
        ]);
        $this->extension->preSubmit($event);

        $message = 'You do not have access to change the fields: country, zip.';
        $formError = new FormError($message);
        $formError->setOrigin($form);
        self::assertContainsEquals($formError, iterator_to_array($form->getErrors(true)));
    }

    /**
     * @param bool $showRestricted
     *
     * @return array [view, form, options, entity]
     */
    private function getTestFormAndFormView(bool $showRestricted = true): array
    {
        $entity = new CmsAddress();
        $options = $this->prepareCorrectOptions(CmsAddress::class, $showRestricted);
        $view = new FormView();
        $view->children = ['city' => new FormView(), 'street' => new FormView(), 'country' => new FormView()];
        $form = $this->factory->create(FormType::class, $entity, $options);
        $form->add('city');
        $form->add('street');
        $form->add('country');

        return [$view, $form, $options, $entity];
    }

    private function prepareCorrectOptions(string $className, bool $showRestricted = true): array
    {
        $this->fieldAclHelper
            ->expects(self::any())
            ->method('isFieldAclEnabled')
            ->with($className)
            ->willReturn(true);
        $this->fieldAclHelper
            ->expects(self::any())
            ->method('isRestrictedFieldsVisible')
            ->with($className)
            ->willReturn($showRestricted);

        return ['data_class' => $className];
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
}

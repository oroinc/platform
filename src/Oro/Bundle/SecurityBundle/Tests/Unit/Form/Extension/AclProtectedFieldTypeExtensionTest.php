<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Form\Extension\AclProtectedFieldTypeExtension;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;

class AclProtectedFieldTypeExtensionTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var TestLogger */
    protected $logger;

    /** @var AclProtectedFieldTypeExtension */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = new TestLogger();

        $this->extension = new AclProtectedFieldTypeExtension(
            $this->securityFacade,
            $this->entityClassResolver,
            $this->doctrineHelper,
            $this->configProvider,
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
        $options = $options = $this->prepareCorrectOptions('Acme\Demo\TestEntity');
        list($dispatcher, $builder) = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, $options);
        $listeners = $dispatcher->getListeners();
        $this->assertCount(2, $listeners);
        $this->assertTrue(array_key_exists('form.pre_bind', $listeners));
        $this->assertTrue(array_key_exists('form.post_bind', $listeners));
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
        $options = [
            'data_class' => 'Acme\Demo\TestEntity',
        ];
        $securityConfig = new Config(
            new EntityConfigId('security', 'Acme\Demo\TestEntity'),
            [
                'field_acl_supported'    => false,
                'field_acl_enabled'      => false,
                'show_restricted_fields' => true
            ]
        );
        $this->entityClassResolver->expects($this->once())
            ->method('isEntity')
            ->with('Acme\Demo\TestEntity')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('Acme\Demo\TestEntity')
            ->willReturn($securityConfig);
        list($dispatcher, $builder) = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, $options);
        $listeners = $dispatcher->getListeners();
        $this->assertCount(0, $listeners);
    }

    public function testBuildFormWithNonEntityClass()
    {
        $options = [
            'data_class' => 'test',
        ];
        $this->entityClassResolver->expects($this->once())
            ->method('isEntity')
            ->with('test')
            ->willReturn(false);
        list($dispatcher, $builder) = $this->getFormBuilderWithEventDispatcher();
        $this->extension->buildForm($builder, $options);
        $listeners = $dispatcher->getListeners();
        $this->assertCount(0, $listeners);
    }

    public function testFinishView()
    {
        list($view, $form, $options) = $this->getTestFormAndFormView(true);
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
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
            "Non accessable field `city` detected in form `form`. Validation errors: ERROR: city error\n",
            $this->logger->getLogs('error')[0]
        );
        $this->assertEquals(
            "Non accessable field `country` detected in form `form`. Validation errors: ERROR: country error\n",
            $this->logger->getLogs('error')[1]
        );
    }

    public function testFinishViewWithShowRestricted()
    {
        list($view, $form, $options) = $this->getTestFormAndFormView(true);
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(
                function ($permission, FieldVote $object) {
                    if ($permission !== 'VIEW') {
                        return false;
                    }
                    return $object->getField() !== 'city';
                }
            );

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
            "Non accessable field `city` detected in form `form`. Validation errors: ERROR: city error\n",
            $this->logger->getLogs('error')[0]
        );
        $this->assertTrue($view->children['city']->isRendered());
        $this->assertFalse($view->children['street']->isRendered());
        $this->assertTrue($view->children['street']->vars['read_only']);
        $this->assertFalse($view->children['country']->isRendered());
        $this->assertTrue($view->children['country']->vars['read_only']);
    }

    public function testPreSubmitOnEmptyData()
    {
        $data = [];
        $form = $this->factory->create('form', new CmsAddress(), []);
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
        $form = $this->factory->create('form', $entity, $options);
        $form->add('city');
        $form->add('street');
        $form->add('country');

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $builder = new FormBuilder('postoffice', null, $dispatcher, $this->factory);
        $builder->setPropertyPath(new PropertyPath('zip'));
        $builder->setAttribute('error_mapping', array());
        $builder->setErrorBubbling(false);
        $builder->setMapped(true);
        $form->add($builder->getForm());

        // add error that should be cleaned
        $form->get('country')->addError(new FormError('test error'));

        $data = [
            'country' => 'some country',
            'city' => 'some city',
            'street' => 'some street',
            'postoffice' => 61000
        ];

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(
                function ($permission, FieldVote $object) {
                    $this->assertEquals('CREATE', $permission);
                    return !in_array($object->getField(), ['country', 'zip']);
                }
            );

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
        $form = $this->factory->create('form', new CmsAddress(), $options);
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
        $options = [
            'data_class' => $className,
        ];

        $securityConfig = new Config(
            new EntityConfigId('security', $className),
            [
                'field_acl_supported'    => true,
                'field_acl_enabled'      => true,
                'show_restricted_fields' => $showRestricted
            ]
        );

        $this->entityClassResolver->expects($this->any())
            ->method('isEntity')
            ->with($className)
            ->willReturn(true);
        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->with($className)
            ->willReturn($securityConfig);

        return $options;
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
}

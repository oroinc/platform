<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Fixtures\TestEntity;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractEnumTypeTestCase extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    public function doTestBuildForm(AbstractType $type)
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->at(0))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$type, 'preSetData']);

        $type->buildForm($builder, []);
    }

    public function doTestPreSetDataForExistingEntity(AbstractType $type)
    {
        $event = $this->getFormEventMock(new TestEntity(123));

        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNullEntity(AbstractType $type)
    {
        $event = $this->getFormEventMock(null);

        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntity(AbstractType $type)
    {
        $enumValueClassName = 'Test\EnumValue';

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formConfig->expects($this->exactly(2))
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    [
                        ['class', null, $enumValueClassName],
                        ['multiple', null, false],
                    ]
                )
            );
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        $event = $this->getFormEventMock(new TestEntity(), $form);

        $this->setExpectationsForLoadDefaultEnumValues(
            $enumValueClassName,
            ['val1']
        );

        $event->expects($this->once())
            ->method('setData')
            ->with('val1');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntityWithMultiEnum(AbstractType $type)
    {
        $enumValueClassName = 'Test\EnumValue';

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formConfig->expects($this->exactly(2))
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    [
                        ['class', null, $enumValueClassName],
                        ['multiple', null, true],
                    ]
                )
            );
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        $event = $this->getFormEventMock(new TestEntity(), $form);

        $this->setExpectationsForLoadDefaultEnumValues(
            $enumValueClassName,
            ['val1', 'val2']
        );

        $event->expects($this->once())
            ->method('setData')
            ->with(['val1', 'val2']);

        $type->preSetData($event);
    }

    protected function doTestSetDefaultOptions(
        AbstractType $type,
        OptionsResolver $resolver,
        $enumCode,
        $multiple = false,
        $expanded = false,
        array $options = []
    ) {
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $enumConfig         = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('multiple', $multiple);
        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));

        $type->setDefaultOptions($resolver);

        $resolvedOptions = $resolver->resolve(
            array_merge(
                $options,
                [
                    'enum_code' => $enumCode,
                    'expanded'  => $expanded
                ]
            )
        );

        $this->assertEquals($multiple, $resolvedOptions['multiple']);
        $this->assertEquals($expanded, $resolvedOptions['expanded']);
        $this->assertEquals($enumCode, $resolvedOptions['enum_code']);
        $this->assertEquals($enumValueClassName, $resolvedOptions['class']);
        $this->assertEquals('name', $resolvedOptions['property']);
        $this->assertNotNull($resolvedOptions['query_builder']);

        unset($resolvedOptions['multiple']);
        unset($resolvedOptions['expanded']);
        unset($resolvedOptions['enum_code']);
        unset($resolvedOptions['class']);
        unset($resolvedOptions['property']);
        unset($resolvedOptions['query_builder']);

        return $resolvedOptions;
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'multiple' => false,
                'expanded' => false
            ]
        );

        return $resolver;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param mixed                                         $entity
     * @param \PHPUnit_Framework_MockObject_MockObject|null $form
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormEventMock($entity = null, $form = null)
    {
        if (!$form) {
            $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        }
        $rootForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->once())
            ->method('getRoot')
            ->will($this->returnValue($rootForm));
        $rootForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($entity));

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        return $event;
    }

    /**
     * @param string $enumValueClassName
     * @param array  $defaultValues
     */
    protected function setExpectationsForLoadDefaultEnumValues($enumValueClassName, $defaultValues)
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getResult'))
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($defaultValues));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('where')
            ->with('e.default = true')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->will($this->returnValue($qb));

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($repo));
    }
}

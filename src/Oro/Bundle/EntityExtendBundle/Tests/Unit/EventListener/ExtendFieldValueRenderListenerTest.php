<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\OptionSetRelation;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\ExtendFieldValueRenderListener;

class ExtendFieldValueRenderListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExtendFieldValueRenderListener
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldTypeHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $extendProvider;

    public function setUp()
    {
        $this->configManger = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManger->expects($this->once())
            ->method('getProvider')
            ->will($this->returnValue($this->extendProvider));
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->fieldTypeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new ExtendFieldValueRenderListener(
            $this->configManger,
            $this->router,
            $this->fieldTypeHelper,
            $this->entityManager
        );
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testBeforeValueRenderProceedCollectionOfCustomEntities($expectedValues)
    {
        $entity = $this->getMock('\StdClass');
        $first = current($expectedValues);
        $value = $this->getCollectionValue($expectedValues);

        $expectedClass = 'Oro\Bundle\UserBundle\Entity\User';
        $routeExpectedClass = 'Oro_Bundle_UserBundle_Entity_User';

        $fieldConfig = $this->getCollectionFieldConfig(
            array(
                $first['firstField'],
                $first['secondField']
            ),
            $expectedClass
        );

        $iteration = 0;
        foreach ($expectedValues as $expectedValue) {
            $this->router->expects($this->at($iteration++))
                ->method('generate')
                ->with(
                    ExtendFieldValueRenderListener::ENTITY_VIEW_ROUTE,
                    array('entityName' => $routeExpectedClass, 'id' => $expectedValue['id']),
                    UrlGeneratorInterface::ABSOLUTE_PATH
                )
                ->will($this->returnValue($expectedValue['route']));
        }

        $this->setupExtendRelationConfig($expectedClass);

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertArrayHasKey('values', $value);

        $actualValues = $value['values'];

        reset($expectedValues);
        foreach ($actualValues as $actual) {
            $expected = current($expectedValues);

            $this->assertEquals($expected['id'], $actual['id']);
            $this->assertEquals($expected['route'], $actual['link']);
            $this->assertEquals("{$expected['firstFieldValue']} {$expected['secondFieldValue']}", $actual['title']);

            next($expectedValues);
        }
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testBeforeValueRenderProceedCollection($expectedValues)
    {
        $entity = $this->getMock('\StdClass');
        $first = current($expectedValues);
        $routeName = 'test_route_name';
        $value = $this->getCollectionValue($expectedValues);
        $class = 'Oro\Bundle\UserBundle\Entity\User';

        $fieldConfig = $this->getCollectionFieldConfig(
            array(
                $first['firstField'],
                $first['secondField']
            ),
            $class
        );

        $this->setupEntityMetadata($routeName, $class);

        $iteration = 0;
        foreach ($expectedValues as $expectedValue) {
            $this->router->expects($this->at($iteration++))
                ->method('generate')
                ->with(
                    $routeName,
                    array('id' => $expectedValue['id']),
                    UrlGeneratorInterface::ABSOLUTE_PATH
                )
                ->will($this->returnValue($expectedValue['route']));
        }

        $this->setupExtendRelationConfig($class, false);

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertArrayHasKey('values', $value);

        $actualValues = $value['values'];

        reset($expectedValues);
        foreach ($actualValues as $actual) {
            $expected = current($expectedValues);

            $this->assertEquals($expected['id'], $actual['id']);
            $this->assertEquals($expected['route'], $actual['link']);
            $this->assertEquals("{$expected['firstFieldValue']} {$expected['secondFieldValue']}", $actual['title']);

            next($expectedValues);
        }
    }

    public function testBeforeValueRenderProceedOptionSets()
    {
        $value = null;
        $className = 'expectedClass';
        $fieldName = 'expectedField';
        $entityConfigFieldId = 42;
        $entityId = 21;
        $label = 'label';

        $entity = $this->getMock('\StdClass', array('getId'));
        $entity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($entityId));

        //setup field config mock
        $fieldConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldConfig->expects($this->once())
            ->method('getFieldType')
            ->will($this->returnValue('optionSet'));
        $fieldConfig->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($className));
        $fieldConfig->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));
        //setup repository mock
        $repository = $this->getMockBuilder(
            'Oro\Bundle\EntityConfigBundle\Entity\Repository\OptionSetRelationRepository'
        )
            ->setMethods(array('findByFieldId'))
            ->disableOriginalConstructor()
            ->getMock();
        $option = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\OptionSet')
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue($label));
        $optionSet = $this->getMock('Oro\Bundle\EntityConfigBundle\Entity\OptionSetRelation');
        $optionSet->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue($option));
        $optionSets = array(
            $optionSet
        );
        $repository->expects($this->once())
            ->method('findByFieldId')
            ->will($this->returnValue($optionSets));
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with(OptionSetRelation::ENTITY_NAME)
            ->will($this->returnValue($repository));
        $this->configManger->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        //setup model mock
        $model = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $model->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($entityConfigFieldId));
        $this->configManger->expects($this->once())
            ->method('getConfigFieldModel')
            ->with($className, $fieldName)
            ->will($this->returnValue($model));

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        //assertions
        $this->assertArrayHasKey('values', $value);
        $actual = current($value['values']);
        $this->assertEquals(array('title' => $label), $actual);
    }

    public function testBeforeValueRenderProceedManyToOneReturnEmptyStingIfEntityClassNotFound()
    {
        $entity = $this->getMock('\StdClass');
        $value = $this->getMock('\StdClass');
        $fieldType = 'manyToOne';
        $field = 'test';

        $fieldConfig = $this->getManyToOneFieldConfig($fieldType);

        $this->setupManyToOneExtendConfig($field, null, $fieldConfig);

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertEmpty($value);
    }

    public function testBeforeValueRenderProceedManyToOneReturnStingIfRouteNotFound()
    {
        $entity = $this->getMock('\StdClass');
        $value = $this->getMock('\StdClass', array('getId'));
        $fieldType = 'manyToOne';
        $field = 'test';
        $id = 42;
        $title = 'test title';
        $class = 'Oro\Bundle\UserBundle\Entity\User';

        $value->$field = $title;
        $value->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $fieldConfig = $this->getManyToOneFieldConfig($fieldType);

        $this->setupManyToOneExtendConfig($field, $class, $fieldConfig);
        $this->setupManyToOneMetadata($class);
        $this->setupExtendRelationConfig($class, false);

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertEquals($value, $title);
    }

    public function testBeforeValueRenderProceedManyToOne()
    {
        $entity = $this->getMock('\StdClass');
        $value = $this->getMock('\StdClass', array('getId'));
        $fieldType = 'manyToOne';
        $field = 'test';
        $routeName = 'test_route_name';
        $id = 42;
        $title = 'test title';
        $class = 'Oro\Bundle\UserBundle\Entity\User';
        $route = '/test-route/42';

        $value->$field = $title;
        $value->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $fieldConfig = $this->getManyToOneFieldConfig($fieldType);

        $this->setupManyToOneExtendConfig($field, $class, $fieldConfig);
        $this->setupManyToOneMetadata($class);
        $this->setupExtendRelationConfig($class, false);
        $this->setupEntityMetadata($routeName, $class);

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $routeName,
                array('id' => $id)
            )
            ->will($this->returnValue($route));

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertArrayHasKey('title', $value);
        $this->assertEquals($value['title'], $title);
        $this->assertArrayHasKey('link', $value);
        $this->assertEquals($value['link'], $route);
    }

    public function testBeforeValueRenderProceedManyToOneWithCustomEntity()
    {
        $entity = $this->getMock('\StdClass');
        $value = $this->getMock('\StdClass', array('getId'));
        $fieldType = 'manyToOne';
        $field = 'test';
        $id = 42;
        $title = 'test title';
        $expectedClass = 'Oro\Bundle\UserBundle\Entity\User';
        $routeExpectedClass = 'Oro_Bundle_UserBundle_Entity_User';
        $route = '/test-route/42';

        $value->$field = $title;
        $value->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $fieldConfig = $this->getManyToOneFieldConfig($fieldType);

        $this->setupManyToOneExtendConfig($field, $expectedClass, $fieldConfig);
        $this->setupManyToOneMetadata($expectedClass);
        $this->setupExtendRelationConfig($expectedClass);

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                ExtendFieldValueRenderListener::ENTITY_VIEW_ROUTE,
                array('entityName' => $routeExpectedClass, 'id' => $id)
            )
            ->will($this->returnValue($route));

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertArrayHasKey('title', $value);
        $this->assertEquals($value['title'], $title);
        $this->assertArrayHasKey('link', $value);
        $this->assertEquals($value['link'], $route);
    }

    public function collectionDataProvider()
    {
        return array(
            array(
                'expectedValues' => array(
                    array(
                        'firstField'       => 'FirstName',
                        'firstFieldValue'  => 'john',
                        'secondField'      => 'LastName',
                        'secondFieldValue' => 'Doe',
                        'id'               => 42,
                        'route'            => '/test-route/42'
                    ),
                    array(
                        'firstField'       => 'FirstName',
                        'firstFieldValue'  => 'jack',
                        'secondField'      => 'LastName',
                        'secondFieldValue' => 'smith',
                        'id'               => 84,
                        'route'            => '/test-route/84'
                    )
                )
            )
        );
    }

    /**
     * @param array $fields
     * @return ArrayCollection
     */
    protected function getCollectionValue(array $fields)
    {
        $value = new ArrayCollection();
        foreach ($fields as $field) {
            $firstFieldName = ucfirst($field['firstField']);
            $secondFieldName = ucfirst($field['secondField']);
            $item = $this->getMock('\StdClass', array("get{$firstFieldName}", "get{$secondFieldName}", 'getId'));
            $item->expects($this->once())
                ->method("get{$firstFieldName}")
                ->will($this->returnValue($field['firstFieldValue']));
            $item->expects($this->once())
                ->method("get{$secondFieldName}")
                ->will($this->returnValue($field['secondFieldValue']));
            $item->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($field['id']));

            $value->add($item);
        }
        return $value;
    }

    /**
     * @param string $expectedClass
     * @param bool   $isCustomEntity
     */
    protected function setupExtendRelationConfig($expectedClass, $isCustomEntity = true)
    {
        $relationExtendConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $relationExtendConfig->expects($this->once())
            ->method('is')
            ->will($this->returnValue($isCustomEntity));
        $this->extendProvider->expects($this->once())
            ->method('getConfig')
            ->with($expectedClass)
            ->will($this->returnValue($relationExtendConfig));
    }

    /**
     * @param array $titles
     * @param string $expectedClass
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCollectionFieldConfig(array $titles, $expectedClass)
    {
        $fieldConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $extendConfig->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('target_title', false, null, $titles),
                        array('target_entity', false, null, $expectedClass),
                    )
                )
            );

        $this->extendProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldConfig)
            ->will($this->returnValue($extendConfig));
        return $fieldConfig;
    }

    /**
     * @param string $field
     * @param string $expectedClass
     * @param FieldConfigId $fieldConfig
     */
    protected function setupManyToOneExtendConfig($field, $expectedClass, FieldConfigId $fieldConfig)
    {
        $extendConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $extendConfig->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('target_field', false, null, $field),
                        array('target_entity', false, null, $expectedClass),
                    )
                )
            );
        $this->extendProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldConfig)
            ->will($this->returnValue($extendConfig));
    }

    /**
     * @param string $fieldType
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManyToOneFieldConfig($fieldType)
    {
        $fieldConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldConfig->expects($this->once())
            ->method('getFieldType')
            ->will($this->returnValue($fieldType));

        $this->fieldTypeHelper->expects($this->once())
            ->method('getUnderlyingType')
            ->with($fieldType)
            ->will($this->returnValue('manyToOne'));
        return $fieldConfig;
    }

    /**
     * @param string $expectedClass
     */
    protected function setupManyToOneMetadata($expectedClass)
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($expectedClass)
            ->will($this->returnValue($metadata));
    }

    /**
     * @param string $routeName
     * @param string $class
     */
    protected function setupEntityMetadata($routeName, $class)
    {
        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->routeView = $routeName;
        $this->configManger->expects($this->once())
            ->method('getEntityMetadata')
            ->with($class)
            ->will($this->returnValue($metadata));
    }
}

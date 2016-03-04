<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\MultipleEntityAttributeNormalizer;

class MultipleEntityAttributeNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflow;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attribute;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var MultipleEntityAttributeNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(array('getReference'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setMethods(array('getAttribute', 'getName'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->attribute = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->setMethods(array('getType', 'getOption', 'getName'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new MultipleEntityAttributeNormalizer($this->registry, $this->doctrineHelper);
    }

    public function testNormalizeExceptionNotCollection()
    {
        $workflowName  = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->getEntityMock();

        $this->workflow->expects($this->once())->method('getName')->will($this->returnValue($workflowName));

        $this->attribute->expects($this->never())->method('getOption')->with('class');

        $this->attribute->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($attributeName));

        $this->setExpectedException(
            'Oro\Bundle\WorkflowBundle\Exception\SerializerException',
            sprintf(
                'Attribute "test_attribute" of workflow "test_workflow" must be a collection or an array,'
                . ' but "%s" given',
                get_class($attributeValue)
            )
        );
        $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue);
    }

    public function testNormalizeExceptionNotInstanceofAttributeClassOption()
    {
        $workflowName  = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = array($this->getEntityMock());

        $this->workflow->expects($this->once())->method('getName')->will($this->returnValue($workflowName));

        $fooClass = $this->getMockClass('FooClass');

        $this->attribute->expects($this->once())->method('getOption')->with('class')
            ->will($this->returnValue($fooClass));

        $this->attribute->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($attributeName));

        $this->setExpectedException(
            'Oro\Bundle\WorkflowBundle\Exception\SerializerException',
            sprintf(
                'Each value of attribute "test_attribute" of workflow "test_workflow" must be an instance of "%s",'
                . ' but "%s" found',
                $fooClass,
                get_class($attributeValue[0])
            )
        );
        $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue);
    }

    public function testDenormalizeExceptionNoEntityManager()
    {
        $workflowName  = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = array($this->getEntityMock());

        $this->workflow->expects($this->once())->method('getName')->will($this->returnValue($workflowName));

        $this->attribute->expects($this->once())->method('getOption')->with('class')
            ->will($this->returnValue(get_class($attributeValue[0])));

        $this->attribute->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($attributeName));

        $this->registry->expects($this->once())->method('getManagerForClass')->with(get_class($attributeValue[0]));

        $this->setExpectedException(
            'Oro\Bundle\WorkflowBundle\Exception\SerializerException',
            sprintf(
                'Attribute "%s" of workflow "%s" contains object of "%s", but it\'s not managed entity class',
                $attributeName,
                $workflowName,
                get_class($attributeValue[0])
            )
        );
        $this->normalizer->denormalize($this->workflow, $this->attribute, array());
    }

    public function testNormalizeEntityArray()
    {
        $attributeValue = array($this->getEntityMock(), $this->getEntityMock());

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->will($this->returnValue(get_class($attributeValue[0])));

        $expectedIds = array(array('id' => 123), array('id' => 456));
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityIdentifier')
            ->will(
                $this->returnValueMap(
                    array(
                        array($attributeValue[0], $expectedIds[0]),
                        array($attributeValue[1], $expectedIds[1]),
                    )
                )
            );

        $this->assertEquals(
            $expectedIds,
            $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    public function testNormalizeEntityCollection()
    {
        $attributeValue = new ArrayCollection(array($this->getEntityMock(), $this->getEntityMock()));

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->will($this->returnValue(get_class($attributeValue[0])));

        $expectedIds = array(array('id' => 123), array('id' => 456));
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityIdentifier')
            ->will(
                $this->returnValueMap(
                    array(
                        array($attributeValue[0], $expectedIds[0]),
                        array($attributeValue[1], $expectedIds[1]),
                    )
                )
            );

        $this->assertEquals(
            $expectedIds,
            $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeAndDenormalizeNull($direction)
    {
        $attributeValue = null;

        $this->workflow->expects($this->never())->method($this->anything());

        if ($direction == 'normalization') {
            $this->assertNull(
                $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
            );
        } else {
            $this->assertNull(
                $this->normalizer->denormalize($this->workflow, $this->attribute, $attributeValue)
            );
        }
    }

    public function testDenormalizeEntity()
    {
        $expectedValue  = array($this->getMock('EntityReference'), $this->getMock('EntityReference'));
        $attributeValue = array(array('id' => 123), array('id' => 456));

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->exactly(3))->method('getOption')
            ->with('class')
            ->will($this->returnValue(get_class($expectedValue[0])));

        $this->registry->expects($this->once())->method('getManagerForClass')
            ->with(get_class($expectedValue[0]))
            ->will($this->returnValue($this->entityManager));

        $this->entityManager->expects($this->exactly(2))->method('getReference')
            ->will(
                $this->returnValueMap(
                    array(
                        array(get_class($expectedValue[0]), $attributeValue[0], $expectedValue[0]),
                        array(get_class($expectedValue[1]), $attributeValue[1], $expectedValue[1]),
                    )
                )
            );

        $this->assertEquals(
            $expectedValue,
            $this->normalizer->denormalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalization($direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->once())->method('getType')->will($this->returnValue('entity'));
        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('multiple')
            ->will($this->returnValue(true));

        $method = 'supports' . ucfirst($direction);
        $this->assertTrue($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalizationForSingle($direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->once())->method('getType')->will($this->returnValue('entity'));

        $method = 'supports' . ucfirst($direction);
        $this->assertFalse($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNotSupportsNormalizationWhenNotEntityType($direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->once())->method('getType')->will($this->returnValue('object'));

        $method = 'supports' . ucfirst($direction);
        $this->assertFalse($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    public function normalizeDirectionDataProvider()
    {
        return array(
            array('normalization'),
            array('denormalization'),
        );
    }

    protected function getEntityMock()
    {
        return $this->getMock('FooEntity');
    }
}

<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\EntityAttributeNormalizer;

class EntityAttributeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $workflow;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $attribute;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EntityAttributeNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

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

        $this->normalizer = new EntityAttributeNormalizer($this->registry, $this->doctrineHelper);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\SerializerException
     * @expectedExceptionMessage Attribute "test_attribute" of workflow "test_workflow" must exist
     */
    public function testNormalizeExceptionNotInstanceofAttributeClassOption()
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->getEntityMock();

        $this->workflow->expects($this->once())->method('getName')->will($this->returnValue($workflowName));

        $fooClass = $this->getMockClass(\stdClass::class);

        $this->attribute->expects($this->once())->method('getOption')->with('class')
            ->will($this->returnValue($fooClass));

        $this->attribute->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($attributeName));

        $this->expectException('Oro\Bundle\WorkflowBundle\Exception\SerializerException');
        $this->expectExceptionMessage(sprintf(
            'Attribute "test_attribute" of workflow "test_workflow" must be an instance of "%s", but "%s" given',
            $fooClass,
            get_class($attributeValue)
        ));
        $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\SerializerException
     * @expectedExceptionMessage Attribute "test_attribute" of workflow "test_workflow" must exist
     */
    public function testDenormalizeExceptionNoEntityManager()
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->getEntityMock();

        $this->workflow->expects($this->once())->method('getName')->will($this->returnValue($workflowName));

        $this->attribute->expects($this->once())->method('getOption')->with('class')
            ->will($this->returnValue(get_class($attributeValue)));

        $this->attribute->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($attributeName));

        $this->registry->expects($this->once())->method('getManagerForClass')->with(get_class($attributeValue));

        $this->expectException('Oro\Bundle\WorkflowBundle\Exception\SerializerException');
        $this->expectExceptionMessage(sprintf(
            'Attribute "%s" of workflow "%s" contains object of "%s", but it\'s not managed entity class',
            $attributeName,
            $workflowName,
            get_class($attributeValue)
        ));
        $this->normalizer->denormalize($this->workflow, $this->attribute, array());
    }

    public function testNormalizeEntity()
    {
        $attributeValue = $this->getEntityMock();

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->will($this->returnValue(get_class($attributeValue)));

        $expectedId = array('id' => 123);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($attributeValue)
            ->will($this->returnValue($expectedId));

        $this->assertEquals(
            $expectedId,
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
        $expectedValue = $this->createMock(\stdClass::class);
        $attributeValue = array('id' => 123);

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->exactly(2))->method('getOption')->with('class')
            ->will($this->returnValue(get_class($expectedValue)));

        $this->registry->expects($this->once())->method('getManagerForClass')->with(get_class($expectedValue))
            ->will($this->returnValue($this->entityManager));

        $this->entityManager->expects($this->once())->method('getReference')
            ->with(get_class($expectedValue), $attributeValue)
            ->will($this->returnValue($expectedValue));

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

        $method = 'supports' . ucfirst($direction);
        $this->assertTrue($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalizationForMultiple($direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())->method($this->anything());

        $this->attribute->expects($this->once())->method('getType')->will($this->returnValue('entity'));
        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('multiple')
            ->will($this->returnValue(true));

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
        return $this->createMock(\stdClass::class);
    }
}

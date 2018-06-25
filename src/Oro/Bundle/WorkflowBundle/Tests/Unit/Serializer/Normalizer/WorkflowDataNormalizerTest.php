<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowDataNormalizer;

class WorkflowDataNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $attributeNormalizer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializer;

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
    protected $attributeManager;

    /**
     * @var WorkflowDataNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->attributeNormalizer = $this->createMock(
            'Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer'
        );
        $this->serializer = $this->createMock('Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer');
        $this->attributeManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();
        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $variableManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\VariableManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setMethods(['getName', 'getVariables', 'getDefinition'])
            ->setConstructorArgs([
                $doctrineHelper,
                $aclManager,
                $restrictionManager,
                null,
                $this->attributeManager,
                null,
                $variableManager
            ])->getMock();

        $workflowDefinition = $this->createMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition');
        $workflowDefinition->expects($this->any())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->workflow->expects($this->any())
            ->method('getVariables')
            ->will($this->returnValue(new ArrayCollection()));
        $this->workflow->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($workflowDefinition));

        $this->attribute = $this->createMock('Oro\Bundle\ActionBundle\Model\Attribute');
        $this->normalizer = new WorkflowDataNormalizer();
    }

    public function testAttributeNormalizersAttribute()
    {
        $this->assertAttributeEmpty('attributeNormalizers', $this->normalizer);

        $this->normalizer->addAttributeNormalizer($this->attributeNormalizer);

        $this->assertAttributeEquals(array($this->attributeNormalizer), 'attributeNormalizers', $this->normalizer);
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider normalizeDirectionDataProvider
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\SerializerException
     * @expectedExceptionMessage Cannot get Workflow. Serializer must implement Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer
     */
    // @codingStandardsIgnoreEnd
    public function testNormalizeExceptionCantGetWorkflow($direction)
    {
        $data = new WorkflowData();
        if ($direction == 'normalization') {
            $this->normalizer->normalize($data);
        } else {
            $this->normalizer->denormalize($data, get_class($data));
        }
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeExceptionNoAttribute($direction)
    {
        $data = new WorkflowData(array('foo' => 'bar'));
        $workflowName = 'test_workflow';

        $this->normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())->method('getWorkflow')->will($this->returnValue($this->workflow));

        $this->attributeManager->expects($this->once())->method('getAttribute')->with('foo');

        if ($direction == 'normalization') {
            $this->workflow->expects($this->once())->method('getName')->will($this->returnValue($workflowName));
            $this->attribute->expects($this->any())->method('getName')->will($this->returnValue('foo'));
            $this->expectException('Oro\Bundle\WorkflowBundle\Exception\SerializerException');
            $this->expectExceptionMessage('Workflow "test_workflow" has no attribute "foo"');
            $this->normalizer->normalize($data);
        } else {
            $this->attributeManager->expects($this->any())->method('getAttributes')->will(
                $this->returnValue(new ArrayCollection(array('for' => $this->attribute)))
            );
            $this->normalizer->denormalize($data, get_class($data));
        }
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeExceptionNoAttributeNormalizer($direction)
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $data = new WorkflowData(array($attributeName => 'bar'));

        $this->normalizer->addAttributeNormalizer($this->attributeNormalizer);
        $this->normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())->method('getWorkflow')->will($this->returnValue($this->workflow));

        $this->workflow->expects($this->once())->method('getName')->will($this->returnValue($workflowName));
        $this->attributeManager->expects($this->any())->method('getAttribute')->with($attributeName)
            ->will($this->returnValue($this->attribute));

        $this->attributeNormalizer->expects($this->once())->method('supports' . ucfirst($direction))
            ->with($this->workflow, $this->attribute, $data->get($attributeName))->will($this->returnValue(false));

        $this->attribute->expects($this->once())->method('getName')->will($this->returnValue($attributeName));

        $this->expectException('Oro\Bundle\WorkflowBundle\Exception\SerializerException');
        $this->expectExceptionMessage(
            sprintf('Cannot handle "%s" of attribute "test_attribute" of workflow "test_workflow"', $direction)
        );

        if ($direction == 'normalization') {
            $this->normalizer->normalize($data);
        } else {
            $this->normalizer->denormalize($data, get_class($data));
        }
    }

    public function testNormalize()
    {
        $denormalizedValue = array('denormalized_value');
        $normalizedValue = array('normalized_value');
        $attributeName = 'test_attribute';

        $data = new WorkflowData(array($attributeName => $denormalizedValue));

        $this->normalizer->addAttributeNormalizer($this->attributeNormalizer);
        $this->normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())->method('getWorkflow')
            ->will($this->returnValue($this->workflow));

        $this->attributeManager->expects($this->once())->method('getAttribute')
            ->with($attributeName)
            ->will($this->returnValue($this->attribute));

        $this->attributeNormalizer->expects($this->once())->method('supportsNormalization')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->will($this->returnValue(true));

        $this->attributeNormalizer->expects($this->once())->method('normalize')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->will($this->returnValue($normalizedValue));

        $this->assertEquals(
            array($attributeName => $normalizedValue),
            $this->normalizer->normalize($data)
        );
    }

    public function testNormalizeTriggersSerializer()
    {
        $denormalizedValue = 'denormalized_value';
        $normalizedValue = array('normalized_value');
        $processedNormalizedValue = array('processed_normalized_value');
        $attributeName = 'test_attribute';

        $data = new WorkflowData(array($attributeName => $denormalizedValue));

        $serializer = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Serializer\WorkflowDataSerializer')
            ->disableOriginalConstructor()
            ->setMethods(array('normalize', 'getWorkflow'))
            ->getMock();

        $this->normalizer->addAttributeNormalizer($this->attributeNormalizer);
        $this->normalizer->setSerializer($serializer);

        $serializer->expects($this->once())->method('getWorkflow')
            ->will($this->returnValue($this->workflow));

        $this->attributeManager->expects($this->once())->method('getAttribute')
            ->with($attributeName)
            ->will($this->returnValue($this->attribute));

        $this->attributeNormalizer->expects($this->once())->method('supportsNormalization')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->will($this->returnValue(true));

        $this->attributeNormalizer->expects($this->once())->method('normalize')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->will($this->returnValue($normalizedValue));

        // As normalized value is not scalar - ask serializer to normalize it
        $serializer->expects($this->once())->method('normalize')->with($normalizedValue)
            ->will($this->returnValue($processedNormalizedValue));

        $this->assertEquals(
            array($attributeName => $processedNormalizedValue),
            $this->normalizer->normalize($data)
        );
    }

    public function testDenormalize()
    {
        $attributeName = 'test_attribute';
        $data = array($attributeName => 'normalized_value');
        $expectedData = new WorkflowData(array($attributeName => 'denormalized_value'));

        $this->attributeManager->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue(new ArrayCollection(array($attributeName => $this->attribute))));
        $this->normalizer->addAttributeNormalizer($this->attributeNormalizer);
        $this->normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())->method('getWorkflow')
            ->will($this->returnValue($this->workflow));

        $this->attributeManager->expects($this->exactly(2))->method('getAttribute')
            ->with($attributeName)
            ->will($this->returnValue($this->attribute));

        $this->attributeNormalizer->expects($this->once())->method('supportsDenormalization')
            ->with($this->workflow, $this->attribute, $data[$attributeName])
            ->will($this->returnValue(true));

        $this->attributeNormalizer->expects($this->once())->method('denormalize')
            ->with($this->workflow, $this->attribute, $data[$attributeName])
            ->will($this->returnValue($expectedData->get($attributeName)));

        $this->assertEquals(
            $expectedData,
            $this->normalizer->denormalize($data, 'Oro\Bundle\WorkflowBundle\Model\WorkflowData')
        );
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data, 'any_value'));
    }

    public function supportsNormalizationDataProvider()
    {
        return array(
            array(null, false),
            array('scalar', false),
            array(new \DateTime(), false),
            array(new WorkflowData(), true),
            array($this->createMock('Oro\Bundle\WorkflowBundle\Model\WorkflowData'), true),
        );
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($type, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization('any_value', $type));
    }

    public function supportsDenormalizationDataProvider()
    {
        return array(
            array(null, false),
            array('string', false),
            array('DateTime', false),
            array('Oro\Bundle\WorkflowBundle\Model\WorkflowData', true),
            array($this->getMockClass('Oro\Bundle\WorkflowBundle\Model\WorkflowData'), true),
        );
    }

    public function normalizeDirectionDataProvider()
    {
        return array(
            array('normalization'),
            array('denormalization'),
        );
    }
}

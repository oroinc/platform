<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowVariableNormalizer;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class WorkflowVariableNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeNormalizer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflow;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $variable;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeManager;

    /**
     * @var WorkflowVariableNormalizer
     */
    protected $normalizer;

    /**
     * Tests setup
     */
    protected function setUp()
    {
        $this->attributeNormalizer = $this->createMock(
            'Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer'
        );
        $this->serializer = $this->createMock('Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer');
        $this->attributeManager = $this->createMock('Oro\Bundle\ActionBundle\Model\AttributeManager');

        $doctrineHelper = $this->createMock('Oro\Bundle\EntityBundle\ORM\DoctrineHelper');
        $aclManager = $this->createMock('Oro\Bundle\WorkflowBundle\Acl\AclManager');
        $restrictionManager = $this->createMock('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager');
        $variableManager = $this->createMock('Oro\Bundle\WorkflowBundle\Model\VariableManager');
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

        $this->variable = $this->createMock('Oro\Bundle\WorkflowBundle\Model\Variable');
        $this->normalizer = new WorkflowVariableNormalizer();
    }

    /**
     * Test variable normalization
     */
    public function testNormalize()
    {
        $variableName = 'test_variable';
        $denormalizedValue = 'denormalized_value';
        $normalizedValue = ['normalized_value'];

        $data = new WorkflowData();
        $data->set($variableName, $denormalizedValue);

        $this->normalizer->addAttributeNormalizer($this->attributeNormalizer);
        $this->normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->any())->method('getWorkflow')
            ->will($this->returnValue($this->workflow));

        $this->attributeNormalizer->expects($this->once())->method('supportsNormalization')
            ->with($this->workflow, $this->variable, $data->get($variableName))
            ->will($this->returnValue(true));

        $this->attributeNormalizer->expects($this->once())->method('normalize')
            ->with($this->workflow, $this->variable, $data->get($variableName))
            ->will($this->returnValue($normalizedValue));

        $this->assertEquals(
            $normalizedValue,
            $this->normalizer->normalizeVariable($this->workflow, $this->variable, $denormalizedValue)
        );
    }

    /**
     * Test variable denormalization
     */
    public function testDenormalize()
    {
        $variableName = 'test_variable';
        $denormalizedValue = 'denormalized_value';
        $data = [$variableName => 'normalized_value'];

        $expectedData = new WorkflowData();
        $expectedData->set($variableName, $denormalizedValue);

        $this->normalizer->addAttributeNormalizer($this->attributeNormalizer);
        $this->normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->any())->method('getWorkflow')
            ->will($this->returnValue($this->workflow));

        $this->attributeNormalizer->expects($this->once())->method('supportsDenormalization')
            ->with($this->workflow, $this->variable, $data[$variableName])
            ->will($this->returnValue(true));

        $this->attributeNormalizer->expects($this->once())->method('denormalize')
            ->with($this->workflow, $this->variable, $data[$variableName])
            ->will($this->returnValue($expectedData->get($variableName)));

        $this->assertEquals(
            $denormalizedValue,
            $this->normalizer->denormalizeVariable($this->workflow, $this->variable, $data[$variableName])
        );
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data, 'any_value'));
    }

    /**
     * @return array
     */
    public function supportsNormalizationDataProvider()
    {
        return [
            [null, false],
            ['scalar', false],
            [new \DateTime(), false],
            [new WorkflowData(), true],
            [$this->createMock('Oro\Bundle\WorkflowBundle\Model\WorkflowData'), true],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($type, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization('any_value', $type));
    }

    /**
     * @return array
     */
    public function supportsDenormalizationDataProvider()
    {
        return [
            [null, false],
            ['string', false],
            ['DateTime', false],
            ['Oro\Bundle\WorkflowBundle\Model\WorkflowData', true],
            [$this->getMockClass('Oro\Bundle\WorkflowBundle\Model\WorkflowData'), true],
        ];
    }

    /**
     * @return array
     */
    public function normalizeDirectionDataProvider()
    {
        return [
            ['normalization'],
            ['denormalization'],
        ];
    }
}

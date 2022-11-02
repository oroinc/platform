<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowDataNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowDataSerializer;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowDataNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeNormalizer;

    /** @var WorkflowAwareSerializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    private $workflow;

    /** @var Attribute|\PHPUnit\Framework\MockObject\MockObject */
    private $attribute;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    protected function setUp(): void
    {
        $this->attributeNormalizer = $this->createMock(AttributeNormalizer::class);
        $this->serializer = $this->createMock(WorkflowAwareSerializer::class);
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $aclManager = $this->createMock(AclManager::class);
        $restrictionManager = $this->createMock(RestrictionManager::class);
        $variableManager = $this->createMock(VariableManager::class);
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getName', 'getVariables', 'getDefinition'])
            ->setConstructorArgs([
                $doctrineHelper,
                $aclManager,
                $restrictionManager,
                null,
                $this->attributeManager,
                null,
                $variableManager
            ])->getMock();

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->any())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->workflow->expects($this->any())
            ->method('getVariables')
            ->willReturn(new ArrayCollection());
        $this->workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        $this->attribute = $this->createMock(Attribute::class);
    }

    private function getWorkflowDataNormalizer(array $attributeNormalizers = []): WorkflowDataNormalizer
    {
        return new WorkflowDataNormalizer($attributeNormalizers);
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeExceptionCantGetWorkflow(string $direction)
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(\sprintf(
            'Cannot get Workflow. Serializer must implement %s',
            WorkflowAwareSerializer::class
        ));

        $data = new WorkflowData();
        $normalizer = $this->getWorkflowDataNormalizer();
        if ($direction === 'normalization') {
            $normalizer->normalize($data);
        } else {
            $normalizer->denormalize($data, get_class($data));
        }
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeExceptionNoAttribute(string $direction)
    {
        $data = new WorkflowData(['foo' => 'bar']);
        $workflowName = 'test_workflow';

        $normalizer = $this->getWorkflowDataNormalizer();
        $normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->attributeManager->expects($this->once())
            ->method('getAttribute')
            ->with('foo');

        if ($direction === 'normalization') {
            $this->workflow->expects($this->once())
                ->method('getName')
                ->willReturn($workflowName);
            $this->attribute->expects($this->any())
                ->method('getName')
                ->willReturn('foo');
            $this->expectException(SerializerException::class);
            $this->expectExceptionMessage('Workflow "test_workflow" has no attribute "foo"');
            $normalizer->normalize($data);
        } else {
            $this->attributeManager->expects($this->any())
                ->method('getAttributes')
                ->willReturn(new ArrayCollection(['for' => $this->attribute]));
            $normalizer->denormalize($data, get_class($data));
        }
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeExceptionNoAttributeNormalizer(string $direction)
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $data = new WorkflowData([$attributeName => 'bar']);

        $normalizer = $this->getWorkflowDataNormalizer([$this->attributeNormalizer]);
        $normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('getName')
            ->willReturn($workflowName);
        $this->attributeManager->expects($this->any())
            ->method('getAttribute')
            ->with($attributeName)
            ->willReturn($this->attribute);

        $this->attributeNormalizer->expects($this->once())
            ->method('supports' . ucfirst($direction))
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->willReturn(false);

        $this->attribute->expects($this->once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot handle "%s" of attribute "test_attribute" of workflow "test_workflow"',
            $direction
        ));

        if ($direction === 'normalization') {
            $normalizer->normalize($data);
        } else {
            $normalizer->denormalize($data, get_class($data));
        }
    }

    public function testNormalize()
    {
        $denormalizedValue = ['denormalized_value'];
        $normalizedValue = ['normalized_value'];
        $attributeName = 'test_attribute';

        $data = new WorkflowData([$attributeName => $denormalizedValue]);

        $normalizer = $this->getWorkflowDataNormalizer([$this->attributeNormalizer]);
        $normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->attributeManager->expects($this->once())
            ->method('getAttribute')
            ->with($attributeName)
            ->willReturn($this->attribute);

        $this->attributeNormalizer->expects($this->once())
            ->method('supportsNormalization')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->willReturn(true);

        $this->attributeNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->willReturn($normalizedValue);

        $this->assertEquals(
            [$attributeName => $normalizedValue],
            $normalizer->normalize($data)
        );
    }

    public function testNormalizeTriggersSerializer()
    {
        $denormalizedValue = 'denormalized_value';
        $normalizedValue = ['normalized_value'];
        $processedNormalizedValue = ['processed_normalized_value'];
        $attributeName = 'test_attribute';

        $data = new WorkflowData([$attributeName => $denormalizedValue]);

        $serializer = $this->createMock(WorkflowDataSerializer::class);

        $normalizer = $this->getWorkflowDataNormalizer([$this->attributeNormalizer]);
        $normalizer->setSerializer($serializer);

        $serializer->expects($this->once())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->attributeManager->expects($this->once())
            ->method('getAttribute')
            ->with($attributeName)
            ->willReturn($this->attribute);

        $this->attributeNormalizer->expects($this->once())
            ->method('supportsNormalization')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->willReturn(true);

        $this->attributeNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->workflow, $this->attribute, $data->get($attributeName))
            ->willReturn($normalizedValue);

        // As normalized value is not scalar - ask serializer to normalize it
        $serializer->expects($this->once())
            ->method('normalize')
            ->with($normalizedValue)
            ->willReturn($processedNormalizedValue);

        $this->assertEquals(
            [$attributeName => $processedNormalizedValue],
            $normalizer->normalize($data)
        );
    }

    public function testDenormalize()
    {
        $attributeName = 'test_attribute';
        $data = [$attributeName => 'normalized_value'];
        $expectedData = new WorkflowData([$attributeName => 'denormalized_value']);

        $this->attributeManager->expects($this->once())
            ->method('getAttributes')
            ->willReturn(new ArrayCollection([$attributeName => $this->attribute]));

        $normalizer = $this->getWorkflowDataNormalizer([$this->attributeNormalizer]);
        $normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->once())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->attributeManager->expects($this->exactly(2))
            ->method('getAttribute')
            ->with($attributeName)
            ->willReturn($this->attribute);

        $this->attributeNormalizer->expects($this->once())
            ->method('supportsDenormalization')
            ->with($this->workflow, $this->attribute, $data[$attributeName])
            ->willReturn(true);

        $this->attributeNormalizer->expects($this->once())
            ->method('denormalize')
            ->with($this->workflow, $this->attribute, $data[$attributeName])
            ->willReturn($expectedData->get($attributeName));

        $this->assertEquals(
            $expectedData,
            $normalizer->denormalize($data, WorkflowData::class)
        );
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $expected)
    {
        $normalizer = $this->getWorkflowDataNormalizer();
        $this->assertEquals($expected, $normalizer->supportsNormalization($data, 'any_value'));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            [null, false],
            ['scalar', false],
            [new \DateTime(), false],
            [new WorkflowData(), true],
            [$this->createMock(WorkflowData::class), true],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(?string $type, bool $expected)
    {
        $normalizer = $this->getWorkflowDataNormalizer();
        $this->assertEquals($expected, $normalizer->supportsDenormalization('any_value', $type));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            ['', false],
            ['string', false],
            ['DateTime', false],
            [WorkflowData::class, true],
            [$this->getMockClass(WorkflowData::class), true],
        ];
    }

    public function normalizeDirectionDataProvider(): array
    {
        return [
            ['normalization'],
            ['denormalization'],
        ];
    }
}

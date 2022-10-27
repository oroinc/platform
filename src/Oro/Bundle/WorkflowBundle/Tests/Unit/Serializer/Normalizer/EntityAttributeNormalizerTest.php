<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\EntityAttributeNormalizer;

class EntityAttributeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    private $workflow;

    /** @var Attribute|\PHPUnit\Framework\MockObject\MockObject */
    private $attribute;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityAttributeNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->attribute = $this->createMock(Attribute::class);

        $this->normalizer = new EntityAttributeNormalizer($this->registry, $this->doctrineHelper);
    }

    public function testNormalizeExceptionNotInstanceofAttributeClassOption()
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('Attribute "test_attribute" of workflow "test_workflow" must exist');

        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects($this->once())
            ->method('getName')
            ->willReturn($workflowName);

        $fooClass = $this->getMockClass(\stdClass::class);

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->willReturn($fooClass);

        $this->attribute->expects($this->once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Attribute "test_attribute" of workflow "test_workflow" must be an instance of "%s", but "%s" given',
            $fooClass,
            get_class($attributeValue)
        ));
        $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue);
    }

    public function testDenormalizeExceptionNoEntityManager()
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('Attribute "test_attribute" of workflow "test_workflow" must exist');

        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects($this->once())
            ->method('getName')
            ->willReturn($workflowName);

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue));

        $this->attribute->expects($this->once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($attributeValue));

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Attribute "%s" of workflow "%s" contains object of "%s", but it\'s not managed entity class',
            $attributeName,
            $workflowName,
            get_class($attributeValue)
        ));
        $this->normalizer->denormalize($this->workflow, $this->attribute, []);
    }

    public function testNormalizeEntity()
    {
        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue));

        $expectedId = ['id' => 123];
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($attributeValue)
            ->willReturn($expectedId);

        $this->assertEquals(
            $expectedId,
            $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeAndDenormalizeNull(string $direction)
    {
        $attributeValue = null;

        $this->workflow->expects($this->never())
            ->method($this->anything());

        if ($direction === 'normalization') {
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
        $attributeValue = ['id' => 123];

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->exactly(2))
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($expectedValue));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($expectedValue))
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(get_class($expectedValue), $attributeValue)
            ->willReturn($expectedValue);

        $this->assertEquals(
            $expectedValue,
            $this->normalizer->denormalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalization(string $direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('entity');

        $method = 'supports' . ucfirst($direction);
        $this->assertTrue($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalizationForMultiple(string $direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('entity');
        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('multiple')
            ->willReturn(true);

        $method = 'supports' . ucfirst($direction);
        $this->assertFalse($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNotSupportsNormalizationWhenNotEntityType(string $direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('object');

        $method = 'supports' . ucfirst($direction);
        $this->assertFalse($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    public function normalizeDirectionDataProvider(): array
    {
        return [
            ['normalization'],
            ['denormalization'],
        ];
    }
}

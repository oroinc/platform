<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\MultipleEntityAttributeNormalizer;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MultipleEntityAttributeNormalizerTest extends \PHPUnit\Framework\TestCase
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

    /** @var MultipleEntityAttributeNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->attribute = $this->createMock(Attribute::class);

        $this->normalizer = new MultipleEntityAttributeNormalizer($this->registry, $this->doctrineHelper);
    }

    public function testNormalizeExceptionNotCollection()
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects($this->once())
            ->method('getName')
            ->willReturn($workflowName);

        $this->attribute->expects($this->never())
            ->method('getOption')
            ->with('class');

        $this->attribute->expects($this->once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Attribute "test_attribute" of workflow "test_workflow" must be a collection or an array,'
            . ' but "%s" given',
            get_class($attributeValue)
        ));
        $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue);
    }

    public function testNormalizeExceptionNotInstanceofAttributeClassOption()
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = [$this->createMock(\stdClass::class)];

        $this->workflow->expects($this->once())
            ->method('getName')
            ->willReturn($workflowName);

        $fooClass = $this->getMockClass('FooClass');

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->willReturn($fooClass);

        $this->attribute->expects($this->once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Each value of attribute "test_attribute" of workflow "test_workflow" must be an instance of "%s",'
            . ' but "%s" found',
            $fooClass,
            get_class($attributeValue[0])
        ));
        $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue);
    }

    public function testDenormalizeExceptionNoEntityManager()
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = [$this->createMock(\stdClass::class)];

        $this->workflow->expects($this->once())
            ->method('getName')
            ->willReturn($workflowName);

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue[0]));

        $this->attribute->expects($this->once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($attributeValue[0]));

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Attribute "%s" of workflow "%s" contains object of "%s", but it\'s not managed entity class',
            $attributeName,
            $workflowName,
            get_class($attributeValue[0])
        ));
        $this->normalizer->denormalize($this->workflow, $this->attribute, []);
    }

    public function testNormalizeEntityArray()
    {
        $attributeValue = [$this->createMock(\stdClass::class), $this->createMock(\stdClass::class)];

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue[0]));

        $expectedIds = [['id' => 123], ['id' => 456]];
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityIdentifier')
            ->willReturnMap([
                [$attributeValue[0], $expectedIds[0]],
                [$attributeValue[1], $expectedIds[1]],
            ]);

        $this->assertEquals(
            $expectedIds,
            $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    public function testNormalizeEntityCollection()
    {
        $attributeValue = new ArrayCollection([
            $this->createMock(\stdClass::class),
            $this->createMock(\stdClass::class)
        ]);

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue[0]));

        $expectedIds = [['id' => 123], ['id' => 456]];
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityIdentifier')
            ->willReturnMap([
                [$attributeValue[0], $expectedIds[0]],
                [$attributeValue[1], $expectedIds[1]],
            ]);

        $this->assertEquals(
            $expectedIds,
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
        $expectedValue = [$this->createMock(\stdClass::class), $this->createMock(\stdClass::class)];
        $attributeValue = [['id' => 123], ['id' => 456]];

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->exactly(3))
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($expectedValue[0]));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($expectedValue[0]))
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->exactly(2))
            ->method('getReference')
            ->willReturnMap([
                [get_class($expectedValue[0]), $attributeValue[0], $expectedValue[0]],
                [get_class($expectedValue[1]), $attributeValue[1], $expectedValue[1]],
            ]);

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
        $this->attribute->expects($this->once())
            ->method('getOption')
            ->with('multiple')
            ->willReturn(true);

        $method = 'supports' . ucfirst($direction);
        $this->assertTrue($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalizationForSingle(string $direction)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('entity');

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

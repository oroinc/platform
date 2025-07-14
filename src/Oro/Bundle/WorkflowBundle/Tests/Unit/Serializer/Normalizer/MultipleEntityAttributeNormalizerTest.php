<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\MultipleEntityAttributeNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MultipleEntityAttributeNormalizerTest extends TestCase
{
    private Workflow&MockObject $workflow;
    private Attribute&MockObject $attribute;
    private EntityManagerInterface&MockObject $entityManager;
    private DoctrineHelper&MockObject $doctrineHelper;
    private MultipleEntityAttributeNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->attribute = $this->createMock(Attribute::class);

        $this->normalizer = new MultipleEntityAttributeNormalizer($this->doctrineHelper);
    }

    public function testNormalizeExceptionNotCollection(): void
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects(self::once())
            ->method('getName')
            ->willReturn($workflowName);

        $this->attribute->expects(self::never())
            ->method('getOption')
            ->with('class');

        $this->attribute->expects(self::once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Attribute "test_attribute" of workflow "test_workflow" must be a collection or an array, but "%s" given',
            get_class($attributeValue)
        ));
        $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue);
    }

    public function testNormalizeExceptionNotInstanceofAttributeClassOption(): void
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = [$this->createMock(\stdClass::class)];

        $this->workflow->expects(self::once())
            ->method('getName')
            ->willReturn($workflowName);

        $fooClass = $this->getMockClass('FooClass');

        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('class')
            ->willReturn($fooClass);

        $this->attribute->expects(self::once())
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

    public function testDenormalizeExceptionNoEntityManager(): void
    {
        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = [$this->createMock(\stdClass::class)];

        $this->workflow->expects(self::once())
            ->method('getName')
            ->willReturn($workflowName);

        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue[0]));

        $this->attribute->expects(self::once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(get_class($attributeValue[0]))
            ->willReturn(null);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Attribute "%s" of workflow "%s" contains object of "%s", but it\'s not managed entity class',
            $attributeName,
            $workflowName,
            get_class($attributeValue[0])
        ));
        $this->normalizer->denormalize($this->workflow, $this->attribute, []);
    }

    public function testNormalizeEntityArray(): void
    {
        $attributeValue = [$this->createMock(\stdClass::class), $this->createMock(\stdClass::class)];

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue[0]));

        $expectedIds = [['id' => 123], ['id' => 456]];
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityIdentifier')
            ->willReturnMap([
                [$attributeValue[0], $expectedIds[0]],
                [$attributeValue[1], $expectedIds[1]],
            ]);

        self::assertEquals(
            $expectedIds,
            $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    public function testNormalizeEntityCollection(): void
    {
        $attributeValue = new ArrayCollection([
            $this->createMock(\stdClass::class),
            $this->createMock(\stdClass::class)
        ]);

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue[0]));

        $expectedIds = [['id' => 123], ['id' => 456]];
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityIdentifier')
            ->willReturnMap([
                [$attributeValue[0], $expectedIds[0]],
                [$attributeValue[1], $expectedIds[1]],
            ]);

        self::assertEquals(
            $expectedIds,
            $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNormalizeAndDenormalizeNull(string $direction): void
    {
        $attributeValue = null;

        $this->workflow->expects(self::never())
            ->method(self::anything());

        if ($direction === 'normalization') {
            self::assertNull(
                $this->normalizer->normalize($this->workflow, $this->attribute, $attributeValue)
            );
        } else {
            self::assertNull(
                $this->normalizer->denormalize($this->workflow, $this->attribute, $attributeValue)
            );
        }
    }

    public function testDenormalizeEntity(): void
    {
        $expectedValue = [$this->createMock(\stdClass::class), $this->createMock(\stdClass::class)];
        $attributeValue = [['id' => 123], ['id' => 456]];

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::exactly(3))
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($expectedValue[0]));

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(get_class($expectedValue[0]))
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnMap([
                [get_class($expectedValue[0]), $attributeValue[0], $expectedValue[0]],
                [get_class($expectedValue[1]), $attributeValue[1], $expectedValue[1]],
            ]);

        self::assertEquals(
            $expectedValue,
            $this->normalizer->denormalize($this->workflow, $this->attribute, $attributeValue)
        );
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalization(string $direction): void
    {
        $attributeValue = 'bar';

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::once())
            ->method('getType')
            ->willReturn('entity');
        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('multiple')
            ->willReturn(true);

        $method = 'supports' . ucfirst($direction);
        self::assertTrue($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalizationForSingle(string $direction): void
    {
        $attributeValue = 'bar';

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::once())
            ->method('getType')
            ->willReturn('entity');

        $method = 'supports' . ucfirst($direction);
        self::assertFalse($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testNotSupportsNormalizationWhenNotEntityType(string $direction): void
    {
        $attributeValue = 'bar';

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::once())
            ->method('getType')
            ->willReturn('object');

        $method = 'supports' . ucfirst($direction);
        self::assertFalse($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    public function normalizeDirectionDataProvider(): array
    {
        return [
            ['normalization'],
            ['denormalization'],
        ];
    }
}

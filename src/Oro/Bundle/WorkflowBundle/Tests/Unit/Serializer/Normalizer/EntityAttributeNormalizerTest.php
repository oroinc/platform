<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\EntityAttributeNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityAttributeNormalizerTest extends TestCase
{
    private Workflow&MockObject $workflow;
    private Attribute&MockObject $attribute;
    private EntityManagerInterface&MockObject $entityManager;
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityAttributeNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->attribute = $this->createMock(Attribute::class);

        $this->normalizer = new EntityAttributeNormalizer($this->doctrineHelper);
    }

    public function testNormalizeExceptionNotInstanceofAttributeClassOption(): void
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('Attribute "test_attribute" of workflow "test_workflow" must exist');

        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects(self::once())
            ->method('getName')
            ->willReturn($workflowName);

        $fooClass = $this->getMockClass(\stdClass::class);

        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('class')
            ->willReturn($fooClass);

        $this->attribute->expects(self::once())
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

    public function testDenormalizeExceptionNoEntityManager(): void
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('Attribute "test_attribute" of workflow "test_workflow" must exist');

        $workflowName = 'test_workflow';
        $attributeName = 'test_attribute';

        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects(self::once())
            ->method('getName')
            ->willReturn($workflowName);

        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue));

        $this->attribute->expects(self::once())
            ->method('getName')
            ->willReturn($attributeName);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
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

    public function testNormalizeEntity(): void
    {
        $attributeValue = $this->createMock(\stdClass::class);

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::once())
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($attributeValue));

        $expectedId = ['id' => 123];
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifier')
            ->with($attributeValue)
            ->willReturn($expectedId);

        self::assertEquals(
            $expectedId,
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
        $expectedValue = $this->createMock(\stdClass::class);
        $attributeValue = ['id' => 123];

        $this->workflow->expects(self::never())
            ->method(self::anything());

        $this->attribute->expects(self::exactly(2))
            ->method('getOption')
            ->with('class')
            ->willReturn(get_class($expectedValue));

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(get_class($expectedValue))
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::once())
            ->method('getReference')
            ->with(get_class($expectedValue), $attributeValue)
            ->willReturn($expectedValue);

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

        $method = 'supports' . ucfirst($direction);
        self::assertTrue($this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    /**
     * @dataProvider normalizeDirectionDataProvider
     */
    public function testSupportsNormalizationForMultiple(string $direction): void
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

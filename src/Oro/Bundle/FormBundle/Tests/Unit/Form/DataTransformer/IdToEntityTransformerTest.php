<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\DataTransformer\IdToEntityTransformer;
use Oro\Bundle\UserBundle\Entity\User;

class IdToEntityTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_NAME = \stdClass::class;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var IdToEntityTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->transformer = new IdToEntityTransformer($this->registry, self::ENTITY_NAME);
    }

    public function testTransformEmptyValue(): void
    {
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->transformer->transform(null));
        $this->assertNull($this->transformer->transform(''));
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?\stdClass $expected): void
    {
        $id = 42;

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with(self::ENTITY_NAME, $id)
            ->willReturn($expected);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_NAME)
            ->willReturn($this->objectManager);

        $this->assertEquals($expected, $this->transformer->transform($id));
    }

    public function transformDataProvider(): array
    {
        return [
            'not found' => [null],
            'found' => [new \stdClass()],
        ];
    }

    public function testReverseTransformEmptyValue(): void
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->assertNull($this->transformer->reverseTransform(null));
        $this->assertNull($this->transformer->reverseTransform('value'));
        $this->assertNull($this->transformer->reverseTransform(new User()));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(array $ids, ?int $expected): void
    {
        $value = new \stdClass();

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($value)
            ->willReturn($ids);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_NAME)
            ->willReturn($metadata);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_NAME)
            ->willReturn($this->objectManager);

        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            'multi identifiers' => [
                'values' => [1001, 2002],
                'expected' => null
            ],
            'one identifier' => [
                'values' => [1001],
                'expected' => 1001
            ],
            'no identifier' => [
                'values' => [],
                'expected' => null
            ],
        ];
    }
}

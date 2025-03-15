<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToJsonTransformer;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntitiesToJsonTransformerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntitiesToJsonTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->transformer = new EntitiesToJsonTransformer($this->doctrine);
    }

    public function testTransformEmptyValue(): void
    {
        self::assertEquals('', $this->transformer->transform([]));
    }

    public function testTransform(): void
    {
        $user0 = $this->createMock(User::class);
        $user0->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $user1 = $this->createMock(User::class);
        $user1->expects(self::once())
            ->method('getId')
            ->willReturn(2);

        $expected =
            json_encode(['entityClass' => get_class($user0), 'entityId' => 1], JSON_THROW_ON_ERROR)
            . ';' .
            json_encode(['entityClass' => get_class($user1), 'entityId' => 2], JSON_THROW_ON_ERROR);

        self::assertEquals($expected, $this->transformer->transform([$user0, $user1]));
    }

    public function testReverseTransformEmptyValue(): void
    {
        self::assertEquals([], $this->transformer->reverseTransform(''));
    }

    public function testReverseTransform(): void
    {
        $user0 = $this->createMock(User::class);
        $user1 = $this->createMock(User::class);

        $value =
            json_encode(['entityClass' => get_class($user0), 'entityId' => 1], JSON_THROW_ON_ERROR)
            . ';' .
            json_encode(['entityClass' => get_class($user1), 'entityId' => 2], JSON_THROW_ON_ERROR);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($user0, $user1);
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturn($repository);

        self::assertEquals([$user0, $user1], $this->transformer->reverseTransform($value));
    }
}

<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToJsonTransformer;
use Oro\Bundle\UserBundle\Entity\User;

class EntitiesToJsonTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EntitiesToJsonTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->transformer = new EntitiesToJsonTransformer($this->entityManager);
    }

    public function testTransformEmptyValue()
    {
        $this->assertEquals('', $this->transformer->transform([]));
    }

    public function testTransform()
    {
        $user0 = $this->createMock(User::class);
        $user0->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $user1 = $this->createMock(User::class);
        $user1->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $expected =
            json_encode(['entityClass' => get_class($user0), 'entityId' => 1], JSON_THROW_ON_ERROR)
            . ';' .
            json_encode(['entityClass' => get_class($user1), 'entityId' => 2], JSON_THROW_ON_ERROR);

        $this->assertEquals($expected, $this->transformer->transform([$user0, $user1]));
    }

    public function testReverseTransformEmptyValue()
    {
        $this->assertEquals([], $this->transformer->reverseTransform(''));
    }

    public function testReverseTransform()
    {
        $user0 = $this->createMock(User::class);
        $user1 = $this->createMock(User::class);

        $value =
            json_encode(['entityClass' => get_class($user0), 'entityId' => 1], JSON_THROW_ON_ERROR)
            . ';' .
            json_encode(['entityClass' => get_class($user1), 'entityId' => 2], JSON_THROW_ON_ERROR);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('OroUserBundle:User');
        $this->entityManager->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($user0, $user1);
        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repository);

        $this->assertEquals([$user0, $user1], $this->transformer->reverseTransform($value));
    }
}

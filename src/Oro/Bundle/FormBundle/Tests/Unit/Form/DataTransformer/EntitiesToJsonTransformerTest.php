<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToJsonTransformer;

class EntitiesToJsonTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var EntitiesToJsonTransformer */
    protected $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transformer = new EntitiesToJsonTransformer($this->entityManager);
    }

    public function testTransformEmptyValue()
    {
        $this->assertEquals('', $this->transformer->transform([]));
    }

    public function testTransform()
    {
        $className = 'Oro\Bundle\UserBundle\Entity\User';
        $user0 = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $user0->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $user1 = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $user1->expects($this->once())
            ->method('getId')
            ->willReturn(2);
        $expected = json_encode(
            [
                'entityClass' => ClassUtils::getClass($user0),
                'entityId' => 1,
            ]
        )
            . ';' .
            json_encode(
                [
                    'entityClass' => ClassUtils::getClass($user1),
                    'entityId' => 2,
                ]
            );

        $this->assertEquals($expected, $this->transformer->transform([$user0, $user1]));
    }

    public function testReverseTransformEmptyValue()
    {
        $this->assertEquals([], $this->transformer->reverseTransform(''));
    }

    public function testReverseTransform()
    {
        $className = 'Oro\Bundle\UserBundle\Entity\User';
        $user0 = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $user1 = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();

        $value = json_encode(
            [
                'entityClass' => ClassUtils::getClass($user0),
                'entityId' => 1,
            ]
        )
            . ';' .
            json_encode(
                [
                    'entityClass' => ClassUtils::getClass($user1),
                    'entityId' => 2,
                ]
            );
        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('OroUserBundle:User');
        $this->entityManager->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $repository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->at(0))
            ->method('find')
            ->willReturn($user0);
        $repository->expects($this->at(1))
            ->method('find')
            ->willReturn($user1);
        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repository);
        $this->assertEquals([$user0, $user1], $this->transformer->reverseTransform($value));
    }
}

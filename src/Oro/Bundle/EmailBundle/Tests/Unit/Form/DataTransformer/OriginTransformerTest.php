<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\DataTransformer\OriginTransformer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class OriginTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OriginTransformer */
    private $transformer;
    /** @var EntityManager */
    private $entityManagerMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->entityManagerMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(['find'])
            ->disableOriginalConstructor()
            ->getMock();

        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $emailOriginHelperMock = $this->createMock('Oro\Bundle\EmailBundle\Tools\EmailOriginHelper');

        $this->transformer = new OriginTransformer(
            $this->entityManagerMock,
            $tokenAccessor,
            $emailOriginHelperMock
        );
    }

    public function testTransform()
    {
        $testOrigin = new TestEmailOrigin('test_id');
        $this->assertEquals('test_id', $this->transformer->transform($testOrigin));
    }

    public function testTransformFail()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->transformer->transform('object should be provided');
    }

    public function testReverseTransformSystemEmail()
    {
        $testOrigin = new TestEmailOrigin(1);

        $this->entityManagerMock
            ->expects($this->any())
            ->method('find')
            ->with($this->equalTo(EmailOrigin::class), $this->equalTo(1))
            ->willReturn($testOrigin);

        $this->assertEquals($testOrigin, $this->transformer->reverseTransform("1|mail@example.com"));
    }
}

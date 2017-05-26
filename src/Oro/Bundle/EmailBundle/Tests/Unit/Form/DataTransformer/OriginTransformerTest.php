<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\DataTransformer\OriginTransformer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;

class OriginTransformerTest extends \PHPUnit_Framework_TestCase
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

        $securiryFacadeMock = $this->createMock('Oro\Bundle\SecurityBundle\SecurityFacade');
        $emailOriginHelperMock = $this->createMock('Oro\Bundle\EmailBundle\Tools\EmailOriginHelper');

        $this->transformer = new OriginTransformer(
            $this->entityManagerMock,
            $securiryFacadeMock,
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

<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\DataTransformer\OriginTransformer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class OriginTransformerTest extends TestCase
{
    private const int TEST_ID = 101;

    private ManagerRegistry&MockObject $doctrine;
    private OriginTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->transformer = new OriginTransformer(
            $this->doctrine,
            $this->createMock(TokenAccessorInterface::class),
            $this->createMock(EmailOriginHelper::class)
        );
    }

    public function testTransform(): void
    {
        $testOrigin = new TestEmailOrigin(self::TEST_ID);
        self::assertEquals(self::TEST_ID, $this->transformer->transform($testOrigin));
    }

    public function testTransformFail(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->transformer->transform('object should be provided');
    }

    public function testReverseTransformSystemEmail(): void
    {
        $testOrigin = new TestEmailOrigin(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EmailOrigin::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(EmailOrigin::class, 1)
            ->willReturn($testOrigin);

        self::assertEquals($testOrigin, $this->transformer->reverseTransform('1|mail@example.com'));
    }
}

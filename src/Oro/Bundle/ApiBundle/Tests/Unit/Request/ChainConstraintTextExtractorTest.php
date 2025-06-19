<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ChainConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractorInterface;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainConstraintTextExtractorTest extends TestCase
{
    /** @var ConstraintTextExtractorInterface[]&MockObject[] */
    private array $extractors = [];
    private ChainConstraintTextExtractor $chainExtractor;

    #[\Override]
    protected function setUp(): void
    {
        $firstExtractor = $this->createMock(ConstraintTextExtractorInterface::class);
        $secondExtractor = $this->createMock(ConstraintTextExtractorInterface::class);

        $this->extractors = [$firstExtractor, $secondExtractor];
        $this->chainExtractor = new ChainConstraintTextExtractor($this->extractors);
    }

    public function testGetConstraintStatusCodeByFirstExtractor(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintStatusCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(400);
        $this->extractors[1]->expects(self::never())
            ->method('getConstraintStatusCode');

        self::assertEquals(400, $this->chainExtractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintStatusCodeBySecondExtractor(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintStatusCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getConstraintStatusCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(401);

        self::assertEquals(401, $this->chainExtractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintStatusCodeWithNullResult(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintStatusCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getConstraintStatusCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);

        self::assertNull($this->chainExtractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintCodeByFirstExtractor(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn('code1');
        $this->extractors[1]->expects(self::never())
            ->method('getConstraintCode');

        self::assertEquals('code1', $this->chainExtractor->getConstraintCode($constraint));
    }

    public function testGetConstraintCodeBySecondExtractor(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn('code2');

        self::assertEquals('code2', $this->chainExtractor->getConstraintCode($constraint));
    }

    public function testGetConstraintCodeWithNullResult(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);

        self::assertNull($this->chainExtractor->getConstraintCode($constraint));
    }

    public function testGetConstraintTypeByFirstExtractor(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintType')
            ->with(self::identicalTo($constraint))
            ->willReturn('first extractor type');
        $this->extractors[1]->expects(self::never())
            ->method('getConstraintType');

        self::assertEquals('first extractor type', $this->chainExtractor->getConstraintType($constraint));
    }

    public function testGetConstraintTypeBySecondExtractor(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintType')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getConstraintType')
            ->with(self::identicalTo($constraint))
            ->willReturn('second extractor type');

        self::assertEquals('second extractor type', $this->chainExtractor->getConstraintType($constraint));
    }

    public function testGetConstraintTypWithNullResult(): void
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintType')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getConstraintType')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);

        self::assertNull($this->chainExtractor->getConstraintType($constraint));
    }
}

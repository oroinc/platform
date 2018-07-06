<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ChainConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractorInterface;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;

class ChainConstraintTextExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainConstraintTextExtractor */
    private $extractor;

    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
    private $extractors = [];

    protected function setUp()
    {
        $this->extractor = new ChainConstraintTextExtractor();

        $firstExtractor = $this->createMock(ConstraintTextExtractorInterface::class);
        $secondExtractor = $this->createMock(ConstraintTextExtractorInterface::class);

        $this->extractor->addExtractor($firstExtractor);
        $this->extractor->addExtractor($secondExtractor);

        $this->extractors = [$firstExtractor, $secondExtractor];
    }

    public function testGetConstraintStatusCodeByFirstExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintStatusCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(400);
        $this->extractors[1]->expects(self::never())
            ->method('getConstraintStatusCode');

        self::assertEquals(400, $this->extractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintStatusCodeBySecondExtractor()
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

        self::assertEquals(401, $this->extractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintStatusCodeWithNullResult()
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

        self::assertNull($this->extractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintCodeByFirstExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(645);
        $this->extractors[1]->expects(self::never())
            ->method('getConstraintCode');

        self::assertEquals(645, $this->extractor->getConstraintCode($constraint));
    }

    public function testGetConstraintCodeBySecondExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getConstraintCode')
            ->with(self::identicalTo($constraint))
            ->willReturn(8456);

        self::assertEquals(8456, $this->extractor->getConstraintCode($constraint));
    }

    public function testGetConstraintCodeWithNullResult()
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

        self::assertNull($this->extractor->getConstraintCode($constraint));
    }

    public function testGetConstraintTypeByFirstExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects(self::once())
            ->method('getConstraintType')
            ->with(self::identicalTo($constraint))
            ->willReturn('first extractor type');
        $this->extractors[1]->expects(self::never())
            ->method('getConstraintType');

        self::assertEquals('first extractor type', $this->extractor->getConstraintType($constraint));
    }

    public function testGetConstraintTypeBySecondExtractor()
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

        self::assertEquals('second extractor type', $this->extractor->getConstraintType($constraint));
    }

    public function testGetConstraintTypWithNullResult()
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

        self::assertNull($this->extractor->getConstraintType($constraint));
    }
}

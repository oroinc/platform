<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ChainConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;

class ChainConstraintTextExtractorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainConstraintTextExtractor */
    protected $extractor;

    /** @var  \PHPUnit_Framework_MockObject_MockObject[] */
    protected $extractors = [];

    protected function setUp()
    {
        $this->extractor = new ChainConstraintTextExtractor();

        $firstExtractor = $this->getMock('Oro\Bundle\ApiBundle\Request\ConstraintTextExtractorInterface');
        $secondExtractor = $this->getMock('Oro\Bundle\ApiBundle\Request\ConstraintTextExtractorInterface');

        $this->extractor->addExtractor($firstExtractor);
        $this->extractor->addExtractor($secondExtractor);

        $this->extractors = [$firstExtractor, $secondExtractor];
    }

    public function testGetConstraintStatusCodeByFirstExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintStatusCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(400);
        $this->extractors[1]->expects($this->never())
            ->method('getConstraintStatusCode');

        $this->assertEquals(400, $this->extractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintStatusCodeBySecondExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintStatusCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getConstraintStatusCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(401);

        $this->assertEquals(401, $this->extractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintStatusCodeWithNullResult()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintStatusCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getConstraintStatusCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);

        $this->assertNull($this->extractor->getConstraintStatusCode($constraint));
    }

    public function testGetConstraintCodeByFirstExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(645);
        $this->extractors[1]->expects($this->never())
            ->method('getConstraintCode');

        $this->assertEquals(645, $this->extractor->getConstraintCode($constraint));
    }

    public function testGetConstraintCodeBySecondExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getConstraintCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(8456);

        $this->assertEquals(8456, $this->extractor->getConstraintCode($constraint));
    }

    public function testGetConstraintCodeWithNullResult()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getConstraintCode')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);

        $this->assertNull($this->extractor->getConstraintCode($constraint));
    }

    public function testGetConstraintTypeByFirstExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintType')
            ->with($this->identicalTo($constraint))
            ->willReturn('first extractor type');
        $this->extractors[1]->expects($this->never())
            ->method('getConstraintType');

        $this->assertEquals('first extractor type', $this->extractor->getConstraintType($constraint));
    }

    public function testGetConstraintTypeBySecondExtractor()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintType')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getConstraintType')
            ->with($this->identicalTo($constraint))
            ->willReturn('second extractor type');

        $this->assertEquals('second extractor type', $this->extractor->getConstraintType($constraint));
    }

    public function testGetConstraintTypWithNullResult()
    {
        $constraint = new AccessGranted();

        $this->extractors[0]->expects($this->once())
            ->method('getConstraintType')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getConstraintType')
            ->with($this->identicalTo($constraint))
            ->willReturn(null);

        $this->assertNull($this->extractor->getConstraintType($constraint));
    }
}

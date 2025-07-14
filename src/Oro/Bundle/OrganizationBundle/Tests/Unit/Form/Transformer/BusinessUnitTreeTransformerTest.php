<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use PHPUnit\Framework\TestCase;

class BusinessUnitTreeTransformerTest extends TestCase
{
    private BusinessUnitTreeTransformer $transformer;

    private $buManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->buManager = $this->createMock(BusinessUnitManager::class);

        $this->transformer = new BusinessUnitTreeTransformer($this->buManager);
    }

    public function testTransform(): void
    {
        $this->assertEquals(0, $this->transformer->transform(null));
        $bu1 = new BusinessUnit();
        $bu1->setId(1);
        $bu2 = new BusinessUnit();
        $bu1->setId(2);
        $this->assertContains(2, $this->transformer->transform([$bu1, $bu2]));
    }

    public function testTransformNullValue(): void
    {
        $this->assertNull($this->transformer->transform(null));
    }

    public function testTransformZerValue(): void
    {
        $this->assertNull($this->transformer->transform(0));
    }

    public function testTransformEmptyArray(): void
    {
        $this->assertSame([], $this->transformer->transform([]));
    }

    public function testPlainValueTransform(): void
    {
        $bu = new BusinessUnit();
        $bu->setId(1);
        $this->assertEquals(1, $this->transformer->transform($bu));
    }

    public function testReverseTransform(): void
    {
        $testResult = new ArrayCollection();
        $bu1 = new BusinessUnit();
        $bu1->setId(1);
        $testResult->add($bu1);
        $bu2 = new BusinessUnit();
        $bu1->setId(2);
        $testResult->add($bu2);

        $buRepo = $this->createMock(BusinessUnitRepository::class);
        $this->buManager->expects($this->any())
            ->method('getBusinessUnitRepo')
            ->willReturn($buRepo);

        $buRepo->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->willReturn($testResult);
        $this->assertSame($testResult, $this->transformer->reverseTransform([1, 2]));

        $bu = new BusinessUnit();
        $bu->setId(1);
        $buRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($bu);
        $this->assertSame($bu, $this->transformer->reverseTransform(1));
    }

    public function testReverseTransformZeroValue(): void
    {
        $this->assertNull($this->transformer->reverseTransform(0));
    }

    public function testReverseTransformNullValue(): void
    {
        $this->assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformEmptyArray(): void
    {
        $this->assertNull($this->transformer->reverseTransform([]));
    }

    public function testReverseTransformEmptyInArray(): void
    {
        $buRepo = $this->createMock(BusinessUnitRepository::class);
        $this->buManager->expects($this->any())
            ->method('getBusinessUnitRepo')
            ->willReturn($buRepo);

        $buRepo->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]]);

        $this->assertNull($this->transformer->reverseTransform([null, '', false, 1, 2]));
    }
}

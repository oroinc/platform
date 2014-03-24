<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Transformer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;

class BusinessUnitTreeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BusinessUnitTreeTransformer
     */
    protected $transformer;

    protected $buManager;

    public function setUp()
    {
        $this->buManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transformer = new BusinessUnitTreeTransformer($this->buManager);

    }

    public function testTransform()
    {
        $this->assertNull($this->transformer->transform(null));
        $bu1 = new BusinessUnit();
        $bu1->setId(1);
        $bu2 = new BusinessUnit();
        $bu1->setId(2);
        $this->assertTrue(in_array(2, $this->transformer->transform([$bu1, $bu2])));
    }

    public function testReverseTransform()
    {

        $testResult = new ArrayCollection();
        $bu1 = new BusinessUnit();
        $bu1->setId(1);
        $testResult->add($bu1);
        $bu2 = new BusinessUnit();
        $bu1->setId(2);
        $testResult->add($bu2);

        $buRepo = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->buManager->expects($this->once())
            ->method('getBusinessUnitRepo')
            ->will($this->returnValue($buRepo));
        $buRepo ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->will($this->returnValue($testResult));
        $this->assertSame($testResult, $this->transformer->reverseTransform([1, 2]));
    }
}

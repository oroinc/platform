<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitTreeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessUnitTreeTypeTest  extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BusinessUnitTreeType
     */
    protected $formType;

    public function setUp()
    {
        $buManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType = new BusinessUnitTreeType($buManager);
    }

    public function testParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }

    public function testName()
    {
        $this->assertEquals('oro_business_unit_tree', $this->formType->getName());
    }

    public function testOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->setDefaultOptions($resolver);

        $this->assertTrue($resolver->isKnown('class'));
        $this->assertTrue($resolver->isKnown('choices'));
    }
}

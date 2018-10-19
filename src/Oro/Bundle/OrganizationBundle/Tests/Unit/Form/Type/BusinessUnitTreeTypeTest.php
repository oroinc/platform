<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitTreeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessUnitTreeTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BusinessUnitTreeType
     */
    protected $formType;

    /**
     * @var BusinessUnitManager
     */
    protected $buManager;

    protected function setUp()
    {
        $this->buManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType  = new BusinessUnitTreeType($this->buManager);
    }

    public function testParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }

    public function testName()
    {
        $this->assertEquals('oro_business_unit_tree', $this->formType->getName());
    }

    public function testOptions()
    {
        $this->buManager->expects($this->once())
            ->method('getBusinessUnitsTree')
            ->will(
                $this->returnValue(
                    [
                        [
                            'id'       => 1,
                            'name'     => 'Root',
                            'children' => [
                                [
                                    'id'   => 2,
                                    'name' => 'Child',
                                ]
                            ]
                        ]
                    ]
                )
            );

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolver->resolve();
        $this->assertTrue($resolver->isDefined('choices'));
    }
}

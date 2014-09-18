<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitType;

class BusinessUnitTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var BusinessUnitType */
    protected $form;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $businessUnitManager */
        $businessUnitManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject $organizationManager */
        $organizationManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject $organizationManager */
        $organizationRepo =
            $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository')
                ->disableOriginalConstructor()
                ->getMock();
        $organizationRepo
            ->expects($this->any())
            ->method('getEnabled')
            ->will($this->returnValue([]));

        $organizationManager
            ->expects($this->any())
            ->method('getOrganizationRepo')
            ->will($this->returnValue($organizationRepo));


        $this->form = new BusinessUnitType($businessUnitManager, $organizationManager);
    }

    public function testSetDefaultOptions()
    {
        $optionResolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $optionResolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                    'ownership_disabled'      => true,
                    'business_unit_tree_ids'  => [],
                    'selected_organizations'  => [],
                    'selected_business_units' => [],
                ]
            );
        $this->form->setDefaultOptions($optionResolver);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->any())
            ->method('add')
            ->will($this->returnSelf());

        $this->form->buildForm($builder, array());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_business_unit', $this->form->getName());
    }
}

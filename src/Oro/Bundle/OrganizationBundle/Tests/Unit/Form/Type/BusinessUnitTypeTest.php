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

        /** @var \PHPUnit_Framework_MockObject_MockObject $securityFacade */
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $businessUnitManager->expects($this->any())
            ->method('getBusinessUnitsTree')
            ->will($this->returnValue([]));

        $businessUnitManager->expects($this->any())
            ->method('getBusinessUnitIds')
            ->will($this->returnValue([]));

        $this->form = new BusinessUnitType($businessUnitManager, $securityFacade);
    }

    public function testSetDefaultOptions()
    {
        $optionResolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $optionResolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                    'ownership_disabled'      => true
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

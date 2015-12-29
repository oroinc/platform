<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\AddressBundle\Validator\Constraints\ValidRegionValidator;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Validator\Constraints\ValidRegion;

class ValidRegionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ValidRegion
     */
    protected $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ValidRegionValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->constraint = new ValidRegion();
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator = new ValidRegionValidator();
        $this->validator->initialize($this->context);
    }

    public function tearDown()
    {
        unset($this->constraint, $this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_address_valid_region', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testIsRegionValidNoCountry()
    {
        $this->context->expects($this->never())
            ->method('addViolationAt');

        $address = $this->createAddress();
        $this->validator->validate($address, $this->constraint);
    }

    public function testIsRegionValidNoRegion()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Country $country */
        $country = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->getMock();
        $country->expects($this->once())
            ->method('hasRegions')
            ->will($this->returnValue(false));

        $this->context->expects($this->never())
            ->method('addViolationAt');

        $address = $this->createAddress();
        $address->setCountry($country);
        $this->validator->validate($address, $this->constraint);
    }

    public function testIsRegionValid()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Country $country */
        $country = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->getMock();
        $country->expects($this->once())
            ->method('hasRegions')
            ->will($this->returnValue(true));
        $country->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Country'));

        $this->context->expects($this->once())
            ->method('getPropertyPath')
            ->will($this->returnValue('test'));
        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with(
                'test.region',
                $this->constraint->message,
                ['{{ country }}' => 'Country']
            );

        $address = $this->createAddress();
        $address->setCountry($country);
        $this->validator->validate($address, $this->constraint);
    }

    /**
     * @param int|null $id
     * @return AbstractAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAddress($id = null)
    {
        /** @var AbstractAddress $result */
        $result = $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress');

        if (null !== $id) {
            $result->setId($id);
        }

        return $result;
    }
}

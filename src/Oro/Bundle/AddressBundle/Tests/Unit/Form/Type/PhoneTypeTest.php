<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\PhoneType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PhoneTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PhoneType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new PhoneType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(3))
            ->method('add')
            ->will($this->returnSelf());

        $builder->expects($this->at(0))
            ->method('add')
            ->with('id', HiddenType::class);

        $builder->expects($this->at(1))
            ->method('add')
            ->with('phone', TextType::class);

        $builder->expects($this->at(2))
            ->method('add')
            ->with('primary', RadioType::class);

        $this->type->buildForm($builder, array());
    }
}

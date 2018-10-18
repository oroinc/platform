<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as SymfonyEmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;

class EmailTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new EmailType();
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
            ->with('email', SymfonyEmailType::class);

        $builder->expects($this->at(2))
            ->method('add')
            ->with('primary', RadioType::class);

        $this->type->buildForm($builder, array());
    }
}

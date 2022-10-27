<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as SymfonyEmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilder;

class EmailTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new EmailType();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                ['id', HiddenType::class],
                ['email', SymfonyEmailType::class],
                ['primary', RadioType::class]
            )
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }
}

<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new UserSelectType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_user_select', $this->type->getName());
    }
}

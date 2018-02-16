<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserSelectType;

class UserSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserSelectType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new UserSelectType();
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_entity_create_or_select_inline', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_user_select', $this->type->getName());
    }
}

<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TagBundle\Form\Type\TagType;

class TagTypeTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var TagType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new TagType();
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnSelf());

        $this->type->buildForm($builder, array());
    }
}

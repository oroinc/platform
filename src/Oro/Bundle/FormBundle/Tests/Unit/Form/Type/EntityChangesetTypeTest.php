<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

class EntityChangesetTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EntityChangesetType
     */
    protected $type;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new EntityChangesetType($this->doctrineHelper);
    }

    public function testGetName()
    {
        $this->assertEquals(EntityChangesetType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('hidden', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['class']);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['mapped' => false]);
        $this->type->setDefaultOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->exactly(2))
            ->method('addViewTransformer')
            ->will($this->returnSelf());
        $builder->expects($this->at(0))
            ->method('addViewTransformer')
            ->with($this->isInstanceOf('Oro\Bundle\FormBundle\Form\DataTransformer\EntityChangesetTransformer'));
        $builder->expects($this->at(1))
            ->method('addViewTransformer')
            ->with($this->isInstanceOf('Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer'));

        $options = ['class' => '\stdClass'];
        $this->type->buildForm($builder, $options);
    }
}

<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TagBundle\Form\Type\EntityTagsSelectType;

class EntityTagsSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityTagsSelectType */
    protected $formType;

    public function setUp()
    {
        $aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $regestry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new EntityTagsSelectType($aclHelper, $regestry);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_tag_entity_tags_selector', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->formType->setDefaultOptions($resolver);
    }
}

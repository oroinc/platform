<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\SecurityBundle\Form\Type\ObjectLabelType;

class ObjectLabelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectLabelType */
    protected $formType;

    protected function setUp()
    {
        $entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()->getMock();
        $this->formType = new ObjectLabelType(new EntityClassNameHelper($entityAliasResolver));
    }

    public function testGetName()
    {
        $this->assertEquals('oro_acl_label', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('hidden', $this->formType->getParent());
    }
}

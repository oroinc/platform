<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\SecurityBundle\Form\Type\ObjectLabelType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ObjectLabelTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectLabelType */
    protected $formType;

    protected function setUp()
    {
        $entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()->getMock();
        $this->formType = new ObjectLabelType(new EntityClassNameHelper($entityAliasResolver));
    }

    public function testGetParent()
    {
        $this->assertEquals(HiddenType::class, $this->formType->getParent());
    }
}

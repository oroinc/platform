<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\SecurityBundle\Form\Type\ObjectLabelType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ObjectLabelTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectLabelType */
    private $formType;

    protected function setUp(): void
    {
        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->formType = new ObjectLabelType(new EntityClassNameHelper($entityAliasResolver));
    }

    public function testGetParent()
    {
        $this->assertEquals(HiddenType::class, $this->formType->getParent());
    }
}

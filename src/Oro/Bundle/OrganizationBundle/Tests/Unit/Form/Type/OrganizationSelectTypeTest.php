<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;

class OrganizationSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationSelectType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new OrganizationSelectType();
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
        $this->assertEquals('oro_jqueryselect2_hidden', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_organization_select', $this->type->getName());
    }
}

<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationSelectTypeTest extends TestCase
{
    private OrganizationSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new OrganizationSelectType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(OroJquerySelect2HiddenType::class, $this->type->getParent());
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_organization_select', $this->type->getName());
    }
}

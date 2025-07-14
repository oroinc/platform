<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;

class OrganizationTypeTest extends TestCase
{
    private OrganizationType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->formType = new OrganizationType($tokenAccessor);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);

        $builder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                ['enabled'],
                ['name'],
                ['description']
            )
            ->willReturnSelf();

        $this->formType->buildForm($builder, []);
    }
}

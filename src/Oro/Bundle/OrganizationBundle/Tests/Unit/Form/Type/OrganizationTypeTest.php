<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\FormBuilder;

class OrganizationTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrganizationType */
    private $formType;

    protected function setUp(): void
    {
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->formType = new OrganizationType($tokenAccessor);
    }

    public function testBuildForm()
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

<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitTreeType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessUnitTreeTypeTest extends TestCase
{
    private BusinessUnitTreeType $formType;
    private BusinessUnitManager $buManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->buManager = $this->createMock(BusinessUnitManager::class);

        $this->formType = new BusinessUnitTreeType($this->buManager);
    }

    public function testParent(): void
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }

    public function testName(): void
    {
        $this->assertEquals('oro_business_unit_tree', $this->formType->getName());
    }

    public function testOptions(): void
    {
        $this->buManager->expects($this->once())
            ->method('getBusinessUnitsTree')
            ->willReturn(
                [
                    [
                        'id'       => 1,
                        'name'     => 'Root',
                        'children' => [
                            [
                                'id'   => 2,
                                'name' => 'Child',
                            ]
                        ]
                    ]
                ]
            );

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolver->resolve();
        $this->assertTrue($resolver->isDefined('choices'));
    }
}

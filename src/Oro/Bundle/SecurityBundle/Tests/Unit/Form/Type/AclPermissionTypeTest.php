<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\AclPermissionType;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AclPermissionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclPermissionType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new AclPermissionType();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $options = [
            'privileges_config' => [
                'field_type' => 'grid'
            ]
        ];
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['accessLevel', 'grid', ['required' => false]],
                ['name', HiddenType::class, ['required' => false]]
            );
        $this->formType->buildForm($builder, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'        => AclPermission::class,
                    'privileges_config' => []
                ]
            );
        $this->formType->configureOptions($resolver);
    }
}

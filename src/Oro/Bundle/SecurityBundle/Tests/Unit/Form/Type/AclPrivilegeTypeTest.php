<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\AclPrivilegeIdentityType;
use Oro\Bundle\SecurityBundle\Form\Type\AclPrivilegeType;
use Oro\Bundle\SecurityBundle\Form\Type\PermissionCollectionType;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AclPrivilegeTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $options = ['privileges_config' => ['field_type' => 'grid']];
        $builder->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive(
                ['identity', AclPrivilegeIdentityType::class, ['required' => false]],
                ['permissions', PermissionCollectionType::class, self::containsEqual($options)]
            );

        (new AclPrivilegeType())->buildForm($builder, $options);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['privileges_config' => [], 'data_class' => AclPrivilege::class]);

        (new AclPrivilegeType())->configureOptions($resolver);
    }

    public function testBuildView(): void
    {
        $view = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);

        $privilegesConfig = ['test'];
        $options = ['privileges_config' => $privilegesConfig];

        (new AclPrivilegeType())->buildView($view, $form, $options);

        self::assertSame($privilegesConfig, $view->vars['privileges_config']);
    }
}

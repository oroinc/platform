<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\AclPrivilegeIdentityType;
use Oro\Bundle\SecurityBundle\Form\Type\AclPrivilegeType;
use Oro\Bundle\SecurityBundle\Form\Type\PermissionCollectionType;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AclPrivilegeTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildForm()
    {
        /** @var FormBuilder|MockObject $builder */
        $builder = $this->getMockBuilder(FormBuilder::class)->disableOriginalConstructor()->getMock();
        $options = ['privileges_config' => ['field_type' => 'grid']];
        $builder->expects(static::exactly(2))
            ->method('add')
            ->withConsecutive(
                ['identity', static::isInstanceOf(AclPrivilegeIdentityType::class), ['required' => false]],
                ['permissions', PermissionCollectionType::class, static::containsEqual($options)]
            );

        (new AclPrivilegeType())->buildForm($builder, $options);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|MockObject $resolver */
        $resolver = $this->getMockBuilder(OptionsResolver::class)->disableOriginalConstructor()->getMock();

        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with(['privileges_config' => [], 'data_class' => AclPrivilege::class,]);

        (new AclPrivilegeType())->configureOptions($resolver);
    }

    public function testBuildView()
    {
        /** @var FormView|MockObject $view */
        $view = $this->getMockBuilder(FormView::class)->disableOriginalConstructor()->getMock();

        /** @var FormInterface|MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();

        $privilegesConfig = ['test'];
        $options = ['privileges_config' => $privilegesConfig];

        (new AclPrivilegeType())->buildView($view, $form, $options);

        static::assertSame($privilegesConfig, $view->vars['privileges_config']);
    }
}

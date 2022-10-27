<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\ChangeRoleSubscriber;
use Oro\Bundle\UserBundle\Form\Type\AclRoleType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AclRoleTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclRoleType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new AclRoleType();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(4))
            ->method('add')
            ->withConsecutive(
                ['label', TextType::class, ['required' => true, 'label' => 'oro.user.role.role.label']],
                [
                    'appendUsers',
                    EntityIdentifierType::class,
                    [
                        'class'    => User::class,
                        'required' => false,
                        'mapped'   => false,
                        'multiple' => true
                    ]
                ],
                [
                    'removeUsers',
                    EntityIdentifierType::class,
                    [
                        'class'    => User::class,
                        'required' => false,
                        'mapped'   => false,
                        'multiple' => true
                    ]
                ],
                ['privileges', HiddenType::class, ['mapped' => false]]
            );
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf(ChangeRoleSubscriber::class));

        $this->formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'    => Role::class,
                    'csrf_token_id' => 'role'
                ]
            );
        $this->formType->configureOptions($resolver);
    }
}

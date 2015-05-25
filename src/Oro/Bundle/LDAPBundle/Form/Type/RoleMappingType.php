<?php

namespace Oro\Bundle\LDAPBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\LDAPBundle\Form\DataTransformer\RolesToIdsTransformer;

class RoleMappingType extends AbstractType
{
    const ROLE_CLASS = 'Oro\Bundle\UserBundle\Entity\Role';

    /** @var Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ldapName', 'text', [
                'label' => 'oro.ldap.transport.ldap.fields.role_ldap.label',
            ])
            ->add(
                $builder->create(
                    'crmRoles',
                    'genemu_jqueryselect2_entity',
                    [
                        'class'    => 'OroUserBundle:Role',
                        'property' => 'label',
                        'multiple' => true,
                        'label' => 'oro.ldap.transport.ldap.fields.role_crm.label',
                    ]
                )
                    ->addModelTransformer(new RolesToIdsTransformer($this->getRoleManager(), static::ROLE_CLASS))
            );
    }

    /**
     * @return EntityManager
     */
    protected function getRoleManager()
    {
        return $this->registry->getManagerForClass(static::ROLE_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ldap_role_mapping';
    }
}

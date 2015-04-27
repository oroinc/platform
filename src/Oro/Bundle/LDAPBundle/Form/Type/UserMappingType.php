<?php

namespace Oro\Bundle\LDAPBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserMappingType extends AbstractType
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

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
        $userManager = $this->getUserManager();
        $metadata = $userManager->getClassMetadata(static::USER_CLASS);
        $fields = $metadata->getFieldNames();

        foreach ($fields as $field) {
            $builder->add($field, 'text', [
                'label' => $field,
            ]);
        }
    }

    /**
     * @return EntityManager
     */
    protected function getUserManager()
    {
        return $this->registry->getManagerForClass(static::USER_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(\Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ldap_user_mapping';
    }
}

<?php

namespace Oro\Bundle\LDAPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EncryptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'none' => 'oro.ldap.oro_ldap_encryption.none',
                'ssl'  => 'oro.ldap.oro_ldap_encryption.ssl',
                'tls'  => 'oro.ldap.oro_ldap_encryption.tls',
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ldap_encryption';
    }
}

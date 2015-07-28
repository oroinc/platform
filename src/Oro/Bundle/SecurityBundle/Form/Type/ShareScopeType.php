<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\Form\Model\Share;

class ShareScopeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->getChoices(),
            ]
        );
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
        return 'oro_share_scope';
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        return [
            Share::SHARE_SCOPE_USER => 'oro.security.share_scopes.user',
            Share::SHARE_SCOPE_BUSINESS_UNIT => 'oro.security.share_scopes.business_unit',
        ];
    }
}

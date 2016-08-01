<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

class AclAccessLevelSelectorType extends AbstractType
{
    const NAME = 'oro_acl_access_level_selector';
    const TRANSLATE_KEY_ACCESS_LEVEL = 'oro.security.access-level';

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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => AccessLevel::getAccessLevelNames(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $parent = $form->getParent()->getParent()->getParent();
        $parentData = $parent->getData();
        if ($parentData instanceof AclPrivilege) {
            $view->vars['identity'] = $parentData->getIdentity()->getId();
            $view->vars['level_label'] = AccessLevel::getAccessLevelName($form->getData());
        }

        $view->vars['translation_prefix'] = self::TRANSLATE_KEY_ACCESS_LEVEL . '.';
    }
}

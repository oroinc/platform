<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Form\Model\Email;

class EmailAddressRecipientsType extends AbstractType
{
    const NAME = 'oro_email_email_address_recipients';

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (null === $parent = $view->parent) {
            return;
        }

        if (!isset($view->parent->vars['data']) || !$view->parent->vars['data'] instanceof Email) {
            return;
        }

        $email = $view->parent->vars['data'];
        $configs = [
            'route_parameters' => [
                'entityClass' => $email->getEntityClass(),
                'entityId'    => $email->getEntityId(),
            ]
        ];

        $view->vars['configs'] = array_merge_recursive($configs, $view->vars['configs']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'tooltip' => false,
            'configs' => [
                'allowClear'         => true,
                'multiple'           => true,
                'route_name'         => 'oro_api_get_email_recipient_autocomplete',
                'separator'          => ';',
                'minimumInputLength' => 1,
                'per_page'           => 100,
                'containerCssClass'  => 'taggable-email',
                'disable_sorting'    => true,
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}

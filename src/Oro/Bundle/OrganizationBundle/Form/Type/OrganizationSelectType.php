<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OrganizationSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'configs' => [
                    'placeholder' => 'oro.organization.form.choose_organization',
                    'result_template_twig' => 'OroOrganizationBundle:Organization:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroOrganizationBundle:Organization:Autocomplete/selection.html.twig'
                ],
                'autocomplete_alias' => 'organization'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_organization_select';
    }
}

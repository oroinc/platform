<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OroEntitySelectOrCreateInlineType extends AbstractType
{
    const NAME = 'oro_entity_create_or_select_inline';

    protected $securityFacade;

    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * Options:
     * - grid_name - name of grid that will be used for entity selection
     * - existing_entity_grid_id - grid row field name used as entity identifier
     * - create_enabled - enables new entity creation
     * - create_acl - ACL resource used to determine that create is allowed, by default CREATE for entity used
     * - create_form_route - route name for creation form
     * - create_form_route_parameters - route parameters for create_form_route_parameters
     *
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'grid_name'
            )
        );

        $resolver->setDefaults(
            array(
                'existing_entity_grid_id' => 'id',
                'create_enabled' => true,
                'create_acl' => null,
                'create_form_route' => null,
                'create_form_route_parameters' => null
            )
        );

        $resolver->setNormalizers(
            array(
                'create_enabled' => function (Options $options, $createEnabled) {
                    if ($createEnabled) {
                        $aclName = $options->get('create_acl');
                        if (empty($aclName)) {
                            $aclObjectName = 'Entity:' . $options->get('entity_class');
                            $createEnabled = $this->securityFacade->isGranted('CREATE', $aclObjectName);
                        } else {
                            $createEnabled = $this->securityFacade->isGranted($aclName);
                        }
                    }

                    $createRouteName = $options->get('create_form_route');
                    return $createEnabled && !empty($createRouteName);
                }
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['grid_name'] = $options['grid_name'];
        $view->vars['existing_entity_grid_id'] = $options['existing_entity_grid_id'];
        $view->vars['create_enabled'] = $options['create_enabled'];
        $view->vars['create_form_route'] = $options['create_form_route'];
        $view->vars['create_form_route_parameters'] = $options['create_form_route_parameters'];
    }
}

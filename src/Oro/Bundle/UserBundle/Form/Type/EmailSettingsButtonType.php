<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class EmailSettingsButtonType extends AbstractType
{
    /** @var  Request */
    protected $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_emailsettings_button';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $currentRoute = $this->request->attributes->get('_route');
        if ($currentRoute == 'oro_user_config') {
            $route = 'oro_user_config_emailsettings';
        } else {
            $route = 'oro_user_profile_config_emailsettings';
        }
        $routeParams = $this->request->attributes->get('_route_params');
        $view->vars['route'] = $route;
        $view->vars['routeParams'] = $routeParams;
    }
}

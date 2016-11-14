<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ButtonsWidgetController extends Controller
{
    /**
     * @Route("/buttons", name="oro_action_buttons_widget_buttons")
     * @Template()
     *
     * @return array
     */
    public function buttonsAction()
    {
        $buttonProvider = $this->get('oro_action.provider.button');
        $buttonSearchContextProvider = $this->get('oro_action.provider.button_search_context');
        $buttonSearchContext = $buttonSearchContextProvider->getButtonSearchContext();

        return [
            'buttons' => $buttonProvider->findAll($buttonSearchContext),
        ];
    }
}

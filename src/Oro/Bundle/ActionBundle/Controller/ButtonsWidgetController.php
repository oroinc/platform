<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\VarDumper\VarDumper;

class ButtonsWidgetController extends Controller
{
    /**
     * @Route("/actions/buttons", name="oro_action_buttons_widget_buttons")
     * @Template()
     *
     * @return array
     */
    public function buttonsAction()
    {
        $buttonProvider = $this->get('oro_action.provider.button');
        $buttonSearchContextProvider = $this->get('oro_action.provider.button_search_context');
        $buttonSearchContext = $buttonSearchContextProvider->getButtonSearchContext();

        //VarDumper::dump($buttonSearchContext);

        return [
            'buttons' => $buttonProvider->findAll($buttonSearchContext),
            'searchContext' => $buttonSearchContext,
        ];
    }
}

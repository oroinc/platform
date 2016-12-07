<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WidgetController extends Controller
{
    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     *
     * @return array
     */
    public function buttonsAction()
    {
        $buttonSearchContext = $this->get('oro_action.provider.button_search_context')->getButtonSearchContext();

        return [
            'buttons' => $this->get('oro_action.provider.button')->findAll($buttonSearchContext),
        ];
    }

    /**
     * @Route("/form/{operationName}", name="oro_action_widget_form")
     *
     * @param Request $request
     * @param string $operationName
     *
     * @return Response
     */
    public function formAction(Request $request, $operationName)
    {
        $handler = $this->get('oro_action.button.operation.widget_form_handler');

        $data = $handler->getData($operationName, $request);

        $flashBag = $this->get('session')->getFlashBag();

        try {
            $result = $handler->handle($data, $request, $flashBag);
        } catch (\Exception $exception) {
            $result = $handler->handleError($data, $exception, $flashBag);
        }

        if ($result instanceof Response) {
            return $result;
        }

        return $this->render($handler->getTemplate($result), $result);
    }
}

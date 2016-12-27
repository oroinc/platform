<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Oro\Bundle\ActionBundle\Model\Operation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WidgetController extends Controller
{
    const DEFAULT_FORM_TEMPLATE = 'OroActionBundle:Operation:form.html.twig';
    const DEFAULT_PAGE_TEMPLATE = 'OroActionBundle:Operation:page.html.twig';

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
            'buttons' => $this->get('oro_action.provider.button')->findAvailable($buttonSearchContext),
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
        $handler = $this->get('oro_action.form.handler.operation_button');

        $result = $handler->process($operationName, $request, $this->get('session')->getFlashBag());

        return $result instanceof Response ? $result : $this->render($this->getFormTemplate($result), $result);
    }

    /**
     * @param array $data
     * @return string
     * @internal param string $operationName
     */
    private function getFormTemplate(array $data)
    {
        $template = self::DEFAULT_FORM_TEMPLATE;

        if (isset($data['operation']) && $data['operation'] instanceof Operation) {
            $frontendOptions = $data['operation']->getDefinition()->getFrontendOptions();

            if (array_key_exists('template', $frontendOptions)) {
                $template = $frontendOptions['template'];
            } elseif (array_key_exists('show_dialog', $frontendOptions) && !$frontendOptions['show_dialog']) {
                $template = self::DEFAULT_PAGE_TEMPLATE;
            }
        }

        return $template;
    }
}

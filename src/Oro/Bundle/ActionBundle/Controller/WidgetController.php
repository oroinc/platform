<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Oro\Bundle\ActionBundle\Handler\OperationFormHandler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Serves action widget actions.
 */
class WidgetController extends AbstractController
{
    const DEFAULT_FORM_TEMPLATE = '@OroAction/Operation/form.html.twig';
    const DEFAULT_PAGE_TEMPLATE = '@OroAction/Operation/page.html.twig';

    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     *
     * @return array
     */
    public function buttonsAction()
    {
        $buttonSearchContext = $this->get(ButtonSearchContextProvider::class)->getButtonSearchContext();

        return [
            'buttons' => $this->get(ButtonProvider::class)->findAvailable($buttonSearchContext),
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
        $handler = $this->get(OperationFormHandler::class);

        $result = $handler->process($operationName, $request, $request->getSession()->getFlashBag());

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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ButtonSearchContextProvider::class,
                ButtonProvider::class,
                OperationFormHandler::class,
            ]
        );
    }
}

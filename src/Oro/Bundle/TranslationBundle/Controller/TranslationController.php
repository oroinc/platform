<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class TranslationController extends BaseController
{
    /**
     * @Route("/", name="oro_translation_translation_index")
     * @Template
     * @AclAncestor("oro_translation_language_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_translation.entity.language.class')
        ];
    }

    /**
     * @Route("/mass_reset/{gridName}/{actionName}", name="oro_translation_mass_reset")
     * @AclAncestor("oro_translation_language_translate")
     *
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function massResetAction($gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        try {
            $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $this->getRequest());
            $data = array_merge(
                ['successful' => $response->isSuccessful(), 'message' => $response->getMessage()],
                $response->getOptions()
            );
        } catch (LogicException $e) {
            $data = [
                'successful' => false,
                'message' => $this->get('translator')->trans('oro.translation.action.reset.nothing_to_reset'),
            ];
        }
        return new JsonResponse($data);
    }
}

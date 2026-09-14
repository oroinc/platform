<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Datagrid\OriginFolderFilterProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Serves EmailOrigin actions.
 * @Route("/emailorigin")
 */
class OriginController extends AbstractController
{
    /**
     * Get list of origins
     *
     * @Route("/list", name="oro_email_emailorigin_list")
     *
     * @return JsonResponse
     */
    public function listAction()
    {
        $originProvider = $this->get(OriginFolderFilterProvider::class);
        return new JsonResponse($originProvider->getListTypeChoices(true));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                OriginFolderFilterProvider::class,
            ]
        );
    }
}

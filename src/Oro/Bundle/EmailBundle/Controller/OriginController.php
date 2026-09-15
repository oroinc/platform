<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Datagrid\OriginFolderFilterProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves EmailOrigin actions.
 */
#[Route(path: '/emailorigin')]
class OriginController extends AbstractController
{
    /**
     * Get list of origins
     *
     * @return JsonResponse
     */
    #[Route(path: '/list', name: 'oro_email_emailorigin_list')]
    public function listAction()
    {
        $originProvider = $this->container->get(OriginFolderFilterProvider::class);

        return new JsonResponse($originProvider->getListTypeChoices(true));
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                OriginFolderFilterProvider::class,
            ]
        );
    }
}

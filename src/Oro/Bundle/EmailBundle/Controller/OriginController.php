<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Oro\Bundle\EmailBundle\Datagrid\OriginFolderFilterProvider;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Serves EmailOrigin actions.
 */
#[Route(path: '/emailorigin')]
class OriginController extends AbstractController
{
    /**
     * Get list of origins
     *
     *
     * @return JsonResponse
     */
    #[Route(path: '/list', name: 'oro_email_emailorigin_list')]
    #[AclAncestor('oro_email_origin_view')]
    public function listAction()
    {
        $originProvider = $this->container->get(OriginFolderFilterProvider::class);
        return new JsonResponse($originProvider->getListTypeChoices(true));
    }

    /**
     * {@inheritdoc}
     */
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

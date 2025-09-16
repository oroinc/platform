<?php

namespace Oro\Bundle\CommentBundle\Controller;

use Oro\Bundle\CommentBundle\Form\Type\CommentTypeApi;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves comment actions.
 */
#[Route(path: '/comments')]
class CommentController extends AbstractController
{
    #[Route(path: '/form', name: 'oro_comment_form')]
    #[Template('@OroComment/Comment/form.html.twig')]
    #[AclAncestor('oro_comment_view')]
    public function getFormAction()
    {
        $form = $this->container->get(CommentTypeApi::class);

        return [
            'form' => $form->createView()
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                CommentTypeApi::class,
            ]
        );
    }
}

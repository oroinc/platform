<?php

namespace Oro\Bundle\CommentBundle\Controller;

use Oro\Bundle\CommentBundle\Form\Type\CommentTypeApi;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Serves comment actions.
 * @Route("/comments")
 */
class CommentController extends AbstractController
{
    /**
     * @Route(
     *      "/form",
     *      name="oro_comment_form"
     * )
     *
     * @AclAncestor("oro_comment_view")
     *
     * @Template("@OroComment/Comment/form.html.twig")
     */
    public function getFormAction()
    {
        $form = $this->get(CommentTypeApi::class);

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                CommentTypeApi::class,
            ]
        );
    }
}

<?php

namespace Oro\Bundle\TestFrameworkBundle\Controller;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The CRUD controller for Item entity.
 */
class ItemController extends AbstractController
{
    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_test_item_index')]
    #[Template('@OroTestFramework/Item/index.html.twig')]
    public function indexAction()
    {
        return [
            'entity_class' => Item::class
        ];
    }

    /**
     * @param Item $item
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_test_item_view', requirements: ['id' => '\d+'])]
    #[Template('@OroTestFramework/Item/view.html.twig')]
    public function viewAction(Item $item)
    {
        return ['entity' => $item];
    }

    #[Route(path: '/create', name: 'oro_test_item_create')]
    public function createAction()
    {
        return new Response();
    }

    /**
     * @param Item $item
     * @return array
     */
    #[Route(path: '/update/{id}', name: 'oro_test_item_update', requirements: ['id' => '\d+'])]
    #[Template('@OroTestFramework/Item/update.html.twig')]
    public function updateAction(Item $item)
    {
        return $this->update($item);
    }

    /**
     * @param Item $item
     * @return array
     */
    protected function update(Item $item)
    {
        return [
            'form' => $this->createFormBuilder($item)->getForm()->createView(),
            'entity' => $item,
        ];
    }
}

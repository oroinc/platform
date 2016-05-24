<?php

namespace Oro\Bundle\TestFrameworkBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class ItemController extends Controller
{
    /**
     * @Route("/", name="oro_test_item_index")
     * @Template
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_test.entity.item.class'),
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_test_item_view", requirements={"id"="\d+"})
     * @Template
     *
     * @param Item $item
     * @return array
     */
    public function viewAction(Item $item)
    {
        return ['entity' => $item];
    }

    /**
     * @Route("/create", name="oro_test_item_create")
     */
    public function createAction()
    {
        return new Response();
    }

    /**
     * @Route("/update/{id}", name="oro_test_item_update", requirements={"id"="\d+"})
     * @Template
     *
     * @param Item $item
     * @return array
     */
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

<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Knp\Menu\Iterator\RecursiveItemIterator;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;

use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Route("/shortcut")
 */
class ShortcutController extends Controller
{
    protected $uris = array();

    /**
     * @Route("actionslist", name="oro_shortcut_actionslist")
     * @Template
     */
    public function actionslistAction()
    {
        /** @var $provider BuilderChainProvider */
        $provider = $this->container->get('oro_menu.builder_chain');
        /**
         * merging shortcuts and application menu
         */
        $shortcuts = $provider->get('shortcuts');
        $menuItems = $provider->get('application_menu');
        $result = array_merge($this->getResults($shortcuts), $this->getResults($menuItems));
        ksort($result);

        return array(
            'actionsList'  => $result,
        );
    }

    /**
     * @param ItemInterface $items
     *
     * @return array
     */
    protected function getResults(ItemInterface $items)
    {
        /** @var $translator TranslatorInterface */
        $translator = $this->get('translator');
        $itemIterator = new RecursiveItemIterator($items);
        $iterator = new \RecursiveIteratorIterator($itemIterator, \RecursiveIteratorIterator::SELF_FIRST);
        $result = array();
        /** @var $item ItemInterface */
        foreach ($iterator as $key => $item) {
            if ($this->isItemAllowed($item)) {
                $result[$key] = $this->getData($item);
                $this->uris[] = $item->getUri();
            }
        }

        return $result;
    }

    /**
     * @param $item ItemInterface
     *
     * @return array
     */
    protected function getData($item)
    {
        $data = [
            'url' => $item->getUri(),
            'label' => $item->getLabel(),
            'description' => $item->getExtra('description')
        ];

        if ($item->getExtra('dialog')) {
            $data['dialog'] = $item->getExtra('dialog');
            $data['dialog_config'] = $item->getExtra('dialog_config');
        }

        return $data;
    }

    /**
     * @param MenuItem $item
     *
     * @return bool
     */
    protected function isItemAllowed(MenuItem $item)
    {
        return (
            $item->getExtra('isAllowed')
            && !in_array($item->getUri(), $this->uris)
            && $item->getUri() !== '#'
            && $item->isDisplayed()
        );
    }
}

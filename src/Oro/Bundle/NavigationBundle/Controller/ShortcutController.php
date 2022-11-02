<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;
use Knp\Menu\Iterator\RecursiveItemIterator;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serves shortcut actions.
 * @Route("/shortcut")
 */
class ShortcutController extends AbstractController
{
    protected $uris = [];

    /**
     * @Route("actionslist", name="oro_shortcut_actionslist")
     * @Template
     */
    public function actionslistAction()
    {
        $provider = $this->get(BuilderChainProvider::class);
        /**
         * merging shortcuts and application menu
         */
        $shortcuts = $provider->get('shortcuts');
        $menuItems = $provider->get('application_menu');
        $result = array_merge($this->getResults($shortcuts), $this->getResults($menuItems));
        ksort($result);

        return [
            'actionsList'  => $result,
        ];
    }

    /**
     * @param ItemInterface $items
     *
     * @return array
     */
    protected function getResults(ItemInterface $items)
    {
        /** @var $translator TranslatorInterface */
        $translator = $this->get(TranslatorInterface::class);
        $itemIterator = new RecursiveItemIterator($items);
        $iterator = new \RecursiveIteratorIterator($itemIterator, \RecursiveIteratorIterator::SELF_FIRST);
        $result = [];
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                BuilderChainProvider::class,
            ]
        );
    }
}

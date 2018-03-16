<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Knp\Menu\ItemInterface;
use Knp\Menu\Iterator\RecursiveItemIterator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @RouteResource("shortcuts")
 * @NamePrefix("oro_api_")
 */
class ShortcutsController extends FOSRestController
{
    protected $uris = [];

    /**
     * REST GET list
     *
     * @param string $query
     *
     * @ApiDoc(
     *  description="Get all shortcuts items for user",
     *  resource=true
     * )
     * @return Response
     */
    public function getAction($query)
    {
        /** @var $provider BuilderChainProvider */
        $provider = $this->container->get('oro_menu.builder_chain');
        /**
         * merging shortcuts and application menu
         */
        $shortcuts = $provider->get('shortcuts');
        $menuItems = $provider->get('application_menu');
        $result = array_merge($this->getResults($shortcuts, $query), $this->getResults($menuItems, $query));

        return $this->handleView(
            $this->view($result, is_array($result) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * @param ItemInterface $items
     * @param $query
     *
     * @return array
     */
    protected function getResults(ItemInterface $items, $query)
    {
        /** @var $translator TranslatorInterface */
        $translator = $this->get('translator');
        $itemIterator = new RecursiveItemIterator($items);
        $iterator = new \RecursiveIteratorIterator($itemIterator, \RecursiveIteratorIterator::SELF_FIRST);
        $result = [];
        /** @var $item ItemInterface */
        foreach ($iterator as $item) {
            if ($this->isItemAllowed($item)) {
                $key = $translator->trans($item->getLabel());
                if (strpos(strtolower($key), strtolower($query)) !== false) {
                    $this->uris[] = $item->getUri();
                    $result[$key] = $this->getData($item);
                }
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
        $data = ['url' => $item->getUri()];

        if ($item->getExtra('dialog')) {
            $data['dialog'] = $item->getExtra('dialog');
            $data['dialog_config'] = $item->getExtra('dialog_config');
        }

        return $data;
    }

    /**
     * @param ItemInterface $item
     *
     * @return bool
     */
    protected function isItemAllowed(ItemInterface $item)
    {
        return (
            $item->getExtra('isAllowed')
            && !in_array($item->getUri(), $this->uris)
            && $item->getUri() !== '#'
            && $item->isDisplayed()
        );
    }
}

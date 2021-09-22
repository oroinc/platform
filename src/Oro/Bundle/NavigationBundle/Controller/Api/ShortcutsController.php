<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Knp\Menu\ItemInterface;
use Knp\Menu\Iterator\RecursiveItemIterator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * REST API controller to get shortcuts items for user.
 */
class ShortcutsController extends AbstractFOSRestController
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
        /** @var BuilderChainProvider $provider */
        $provider = $this->container->get('oro_menu.builder_chain');
        /**
         * merging shortcuts and application menu
         */
        $shortcuts = $provider->get('shortcuts');
        $menuItems = $provider->get('application_menu');
        $result = array_merge($this->getResults($shortcuts, $query), $this->getResults($menuItems, $query));

        return $this->handleView($this->view($result, Response::HTTP_OK));
    }

    protected function getResults(ItemInterface $items, string $query): array
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $itemIterator = new RecursiveItemIterator($items);
        $iterator = new \RecursiveIteratorIterator($itemIterator, \RecursiveIteratorIterator::SELF_FIRST);
        $result = [];
        /** @var ItemInterface $item */
        foreach ($iterator as $item) {
            if ($this->isItemAllowed($item)) {
                $key = $translator->trans((string) $item->getLabel());
                if (str_contains(strtolower($key), strtolower($query))) {
                    $this->uris[] = $item->getUri();
                    $result[$key] = $this->getData($item);
                }
            }
        }

        return $result;
    }

    protected function getData(ItemInterface $item): array
    {
        $data = ['url' => $item->getUri()];

        if ($item->getExtra('dialog')) {
            $data['dialog'] = $item->getExtra('dialog');
            $data['dialog_config'] = $item->getExtra('dialog_config');
        }

        return $data;
    }

    protected function isItemAllowed(ItemInterface $item): bool
    {
        return (
            $item->getExtra('isAllowed')
            && !in_array($item->getUri(), $this->uris)
            && $item->getUri() !== '#'
            && $item->isDisplayed()
        );
    }
}

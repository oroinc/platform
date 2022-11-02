<?php

namespace Oro\Bundle\NavigationBundle\Entity\Builder;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NavigationBundle\Entity\AbstractNavigationItem;
use Oro\Bundle\NavigationBundle\Entity\AbstractPinbarTab;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Provider\PinbarTabTitleProviderInterface;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizerInterface;

/**
 * Builds pinbar entity.
 */
class PinbarTabBuilder extends AbstractBuilder
{
    /**
     * @var string
     */
    protected $navigationItemClassName;

    /**
     * @var PinbarTabUrlNormalizerInterface
     */
    private $pinbarTabUrlNormalizer;

    /**
     * @var PinbarTabTitleProviderInterface
     */
    private $pinbarTabTitleProvider;

    public function __construct(
        EntityManager $em,
        PinbarTabUrlNormalizerInterface $pinbarTabUrlNormalizer,
        PinbarTabTitleProviderInterface $pinbarTabTitleProvider,
        string $type
    ) {
        parent::__construct($em, $type);

        $this->pinbarTabTitleProvider = $pinbarTabTitleProvider;
        $this->pinbarTabUrlNormalizer = $pinbarTabUrlNormalizer;
    }

    /**
     * Build navigation item
     *
     * @param $params
     * @return object|null
     */
    public function buildItem($params)
    {
        if (isset($params['url'])) {
            $params['url'] = $this->pinbarTabUrlNormalizer->getNormalizedUrl($params['url']);
        }

        /** @var AbstractNavigationItem $navigationItem */
        $navigationItem = new $this->navigationItemClassName($params);
        $navigationItem->setType($this->getType());

        /** @var AbstractPinbarTab $pinbarTabItem */
        $pinbarTabItem = new $this->className();
        $pinbarTabItem->setItem($navigationItem);

        [$title, $titleShort] = $this->pinbarTabTitleProvider->getTitles($navigationItem, $this->className);
        $pinbarTabItem->setTitle($title);
        $pinbarTabItem->setTitleShort($titleShort);

        $pinbarTabItem->setMaximized(!empty($params['maximized']));

        return $pinbarTabItem;
    }

    /**
     * Find navigation item
     *
     * @param  int            $itemId
     * @return PinbarTab|null
     */
    public function findItem($itemId)
    {
        return $this->getEntityManager()->find($this->className, $itemId);
    }

    /**
     * @param string $navigationItemClassName
     *
     * @return PinbarTabBuilder
     */
    public function setNavigationItemClassName($navigationItemClassName)
    {
        $this->navigationItemClassName = $navigationItemClassName;

        return $this;
    }
}

<?php

namespace Oro\Bundle\NavigationBundle\Entity\Builder;

use Oro\Bundle\NavigationBundle\Entity\PinbarTab;

class PinbarTabBuilder extends AbstractBuilder
{
    /**
     * @var string
     */
    protected $navigationItemClassName;

    /**
     * Build navigation item
     *
     * @param $params
     * @return object|null
     */
    public function buildItem($params)
    {
        $navigationItem = new $this->navigationItemClassName($params);
        $navigationItem->setType($this->getType());

        $pinbarTabItem = new $this->className();
        $pinbarTabItem->setItem($navigationItem);
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

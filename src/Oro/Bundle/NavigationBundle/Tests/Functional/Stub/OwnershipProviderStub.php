<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Stub;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\Provider\OwnershipProviderInterface;

// TODO: remove this class in ticket BB-5468
class OwnershipProviderStub implements OwnershipProviderInterface
{
    /** @var MenuUpdateInterface[] */
    private $menuUpdates = [];

    /**
     * @param MenuUpdateInterface[] $menuUpdates
     */
    public function __construct(array $menuUpdates)
    {
        $this->menuUpdates = $menuUpdates;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuUpdates($menuName)
    {
        return $this->menuUpdates;
    }
}

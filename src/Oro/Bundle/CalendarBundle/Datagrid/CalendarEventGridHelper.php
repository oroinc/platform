<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class CalendarEventGridHelper
{
    /** @var Router */
    protected $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $gridName
     * @param string $keyName
     * @param array  $node
     *
     * @return callable
     */
    public function getDeleteLinkProperty($gridName, $keyName, $node)
    {
        if (!isset($node['route'])) {
            return false;
        }

        $router = $this->router;
        $route  = $node['route'];

        return function (ResultRecord $record) use ($gridName, $router, $route) {
            return $router->generate(
                $route,
                [
                    'id'                 => $record->getValue('id'),
                    'notifyInvitedUsers' => true,
                ]
            );
        };
    }
}

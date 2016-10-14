<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class DatagridRouteHelper
{
    /** @var Router */
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $name
     * @param string $gridName
     * @param array $params
     * @param int $referenceType
     *
     * @return string
     */
    public function generate($name, $gridName, $params = [], $referenceType = Router::ABSOLUTE_PATH)
    {
        return $this->router->generate(
            $name,
            ['grid' => [$gridName => http_build_query($params)]],
            $referenceType
        );
    }
}

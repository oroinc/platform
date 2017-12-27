<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Stub;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class UrlGeneratorStub implements UrlGeneratorInterface
{
    /**
     * @var array
     */
    private static $declaredRoutes = [
        'route_name' => [
            ['view'],
            ['_controller' => 'controller::action'],
            ['_method' => 'GET'],
            [['text', '/baz']],
            [],
            []
        ],
        'test' => [
            ['view'],
            ['_controller' => 'action'],
            ['_method' => 'GET'],
            [['text', '/baz']],
            [],
            []
        ]
    ];

    /**
     * @var RequestContext
     */
    private $context;

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }
}

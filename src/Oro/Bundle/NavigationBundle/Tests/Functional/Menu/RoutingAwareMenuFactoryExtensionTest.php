<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Menu;

use Oro\Bundle\NavigationBundle\Menu\RoutingAwareMenuFactoryExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class RoutingAwareMenuFactoryExtensionTest extends WebTestCase
{
    const INDEX_PHP_FILE = 'index.php';

    /**
     * @var RoutingAwareMenuFactoryExtension
     */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->extension = self::getContainer()->get('oro_navigation.menu_extension.routing');
    }

    public function testBuildOptions()
    {
        $router = self::getContainer()->get('router');
        $router->getContext()->setBaseUrl('index.php');
        $this->assertStringStartsWith(
            self::INDEX_PHP_FILE,
            ltrim($router->generate('oro_test_item_index', [], RouterInterface::ABSOLUTE_PATH), '/')
        );

        $options = $this->extension->buildOptions(['route' => 'oro_test_item_index']);
        $this->assertStringStartsNotWith(self::INDEX_PHP_FILE, $options['uri']);
    }
}

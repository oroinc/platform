<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional;

use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;

use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Tests\Functional\Stub\OwnershipProviderStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Component\Yaml\Yaml;

class MenuUpdateBuilderTest extends WebTestCase
{
    use EntityTrait;

    /** @var MenuManipulator */
    protected $manipulator;

    /** @var array */
    protected $builders;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->manipulator = new MenuManipulator();
    }

    public function testMenuTreeBuilder()
    {
        $loader = new FolderContentCumulativeLoader(realpath(dirname(__DIR__) . '/Fixtures/menu_updates'));
        $resources = $loader->load('TestBundle', '/');
        foreach ($resources->data as $resource) {
            $fixtures = Yaml::parse(file_get_contents($resource));

            $menus = $this->getMenus($fixtures['menu'], ['menu']);
            $updates = $this->getMenuUpdates($fixtures['updates']);

            $this->applyMenuUpdate($menus, $updates);

            $result = $this->getMenus($fixtures['result_menu'], ['menu']);

            $this->assertEquals($result, $menus, sprintf('"result_menu" not equals "menu" in %s', basename($resource)));
        }
    }

    /**
     * @param array $updates
     *
     * @return MenuUpdate[]
     */
    protected function getMenuUpdates($updates)
    {
        foreach ($updates as $key => $update) {
            $update['custom'] = true;
            $updates[$key] = $this->getEntity(MenuUpdate::class, $update);
        }

        return $updates;
    }

    /**
     * @param string $fileName
     *
     * @return array
     */
    protected function getFixtures($fileName)
    {
        $fixturesPath = realpath(dirname(__DIR__) . '/Fixtures/menu_updates');

        return Yaml::parse(file_get_contents($fixturesPath . '/' . $fileName));
    }

    /**
     * @param array  $configuration
     * @param array  $aliases
     *
     * @return ItemInterface[]
     */
    protected function getMenus(array $configuration, array $aliases)
    {
        $configurationBuilder = $this->getContainer()->get('oro_menu.configuration_builder');
        $configurationBuilder->setConfiguration($configuration);

        $builderChain = clone $this->getContainer()->get('oro_menu.builder_chain');

        $menus = [];
        foreach ($aliases as $alias) {
            $menus[$alias] = $builderChain->get($alias);
        }

        return $menus;
    }

    /**
     * @param ItemInterface[] $menus
     * @param array           $updates
     * @param string          $area
     */
    protected function applyMenuUpdate(array $menus, array $updates, $area = 'default')
    {
        $provider = new OwnershipProviderStub($updates);

        $builder = new MenuUpdateBuilder($this->getContainer()->get('oro_locale.helper.localization'));
        $builder->addProvider($provider, $area, 0);

        foreach ($menus as $menu) {
            $builder->build($menu);
        }
    }
}

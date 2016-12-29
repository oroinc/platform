<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;

use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
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

    public function testBuild()
    {
        $loader = new FolderContentCumulativeLoader(realpath(dirname(__DIR__).'/Fixtures/menu_updates'));
        $resources = $loader->load('TestBundle', '/');
        foreach ($resources->data as $resource) {
            $fixtures = Yaml::parse(file_get_contents($resource));
            $menus = $this->getMenus($fixtures['menu'], ['menu']);
            $updates = $this->getMenuUpdates($fixtures['updates']);

            $this->applyMenuUpdate(
                $menus,
                $updates,
                $this->getContainer()->getParameter('oro_navigation.menu_update.scope_type')
            );

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
        $fixturesPath = realpath(dirname(__DIR__).'/Fixtures/menu_updates');

        return Yaml::parse(file_get_contents($fixturesPath.'/'.$fileName));
    }

    /**
     * @param array $configuration
     * @param array $aliases
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
     * @param string          $scopeType
     */
    protected function applyMenuUpdate(array $menus, array $updates, $scopeType)
    {
        $repo = $this->getMockBuilder(MenuUpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(MenuUpdate::class)
            ->willReturn($repo);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(MenuUpdate::class)
            ->willReturn($manager);

        $repo->expects($this->any())
            ->method('findMenuUpdatesByScopeIds')
            ->willReturn($updates);

        $builder = new MenuUpdateBuilder(
            $this->getContainer()->get('oro_locale.helper.localization'),
            $this->getContainer()->get('oro_scope.scope_manager'),
            $doctrine
        );
        $builder->setScopeType($scopeType);
        $builder->setClassName(MenuUpdate::class);

        foreach ($menus as $menu) {
            $builder->build($menu);
        }
    }
}

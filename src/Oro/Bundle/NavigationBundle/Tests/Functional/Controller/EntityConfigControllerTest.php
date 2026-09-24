<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Controller;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks that the entity config pages of a menu item are not available: it is a system record managed on the page
 * of its menu.
 */
class EntityConfigControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    /**
     * @dataProvider entityConfigPageDataProvider
     */
    public function testEntityConfigPageIsNotFound(string $method, string $route, \Closure $routeParams): void
    {
        $configModel = $this->getConfigModel();

        $this->client->request($method, $this->getUrl($route, $routeParams($configModel)));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function entityConfigPageDataProvider(): array
    {
        return [
            'entity config view page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_view',
                'routeParams' => fn (EntityConfigModel $configModel) => ['id' => $configModel->getId()],
            ],
            'entity config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_update',
                'routeParams' => fn (EntityConfigModel $configModel) => ['id' => $configModel->getId()],
            ],
            'entity config update page, post' => [
                'method' => 'POST',
                'route' => 'oro_entityconfig_update',
                'routeParams' => fn (EntityConfigModel $configModel) => ['id' => $configModel->getId()],
            ],
            'create field page' => [
                'method' => 'GET',
                'route' => 'oro_entityextend_field_create',
                'routeParams' => fn (EntityConfigModel $configModel) => ['id' => $configModel->getId()],
            ],
            'field config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_field_update',
                'routeParams' => fn (EntityConfigModel $configModel) => [
                    'id' => $configModel->getFields()->first()->getId()
                ],
            ],
        ];
    }

    private function getConfigModel(): EntityConfigModel
    {
        $configModel = self::getContainer()->get('doctrine')
            ->getManagerForClass(EntityConfigModel::class)
            ->getRepository(EntityConfigModel::class)
            ->findOneBy(['className' => MenuUpdate::class]);

        self::assertNotNull($configModel, 'The entity config of a menu item is not found');

        return $configModel;
    }
}

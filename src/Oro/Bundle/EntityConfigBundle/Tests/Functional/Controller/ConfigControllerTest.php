<?php

namespace Functional\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    /** @dataProvider updateActionDataProvider */
    public function testUpdateAction(
        string $method,
        string $route,
        \Closure $routeParams,
        string $className,
        int $expectedStatusCode
    ): void {
        $fileEntityModel = $this->getFileConfigModel($className);

        $this->client
            ->request(
                $method,
                $this->getUrl($route, $routeParams($fileEntityModel))
            );

        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, $expectedStatusCode);
    }

    public function updateActionDataProvider(): array
    {
        return [
            'manageable file item entity try to get entity config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getId()],
                'className' => FileItem::class,
                'expectedStatusCode' => Response::HTTP_OK,
            ],
            'manageable file item entity try post to entity config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getId()],
                'className' => FileItem::class,
                'expectedStatusCode' => Response::HTTP_OK,
            ],
            'manageable file item entity try to get entity field config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_field_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getFields()->current()->getId()],
                'className' => FileItem::class,
                'expectedStatusCode' => Response::HTTP_OK,
            ],
            'manageable file item entity try post to entity field config update page' => [
                'method' => 'POST',
                'route' => 'oro_entityconfig_field_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getFields()->current()->getId()],
                'className' => FileItem::class,
                'expectedStatusCode' => Response::HTTP_OK,
            ],
            'not manageable file entity try to get entity config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getId()],
                'className' => File::class,
                'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            ],
            'not manageable file entity try post to entity config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getId()],
                'className' => File::class,
                'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            ],
            'not manageable file entity try to get entity field config update page' => [
                'method' => 'GET',
                'route' => 'oro_entityconfig_field_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getFields()->current()->getId()],
                'className' => File::class,
                'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            ],
            'not manageable file entity try post to entity field config update page' => [
                'method' => 'POST',
                'route' => 'oro_entityconfig_field_update',
                'routeParams' => fn (EntityConfigModel $cm) => ['id' => $cm->getFields()->current()->getId()],
                'className' => File::class,
                'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            ]
        ];
    }

    private function getFileConfigModel(string $entityClassName): EntityConfigModel
    {
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $em = $doctrine->getManagerForClass(EntityConfigModel::class);
        $repo = $em->getRepository(EntityConfigModel::class);
        return $repo->findOneBy(['className' => $entityClassName]);
    }
}

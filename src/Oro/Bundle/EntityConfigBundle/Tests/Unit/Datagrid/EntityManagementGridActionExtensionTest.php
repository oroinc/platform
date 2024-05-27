<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityConfigBundle\Datagrid\EntityManagementGridActionExtension;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Voter\EntityManagementConfigVoter;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EntityManagementGridActionExtensionTest extends TestCase
{
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    private Registry|MockObject $doctrine;

    private EntityManagementGridActionExtension $extension;

    private EntityManagementConfigVoter $voter;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->doctrine = $this->createMock(Registry::class);
        $this->voter = new EntityManagementConfigVoter();
        $this->extension = new EntityManagementGridActionExtension(
            $this->authorizationChecker,
            $this->doctrine
        );
    }

    /** @dataProvider isApplicableDataProvider */
    public function testIsApplicable(string $rootEntity, string $datasourceType, bool $expectedResult): void
    {
        $datagridConfiguration = DatagridConfiguration::create([]);
        $datagridConfiguration
            ->getOrmQuery()
            ->addSelect('re')
            ->addFrom($rootEntity, 're');
        $datagridConfiguration->setDatasourceType($datasourceType);

        self::assertEquals($expectedResult, $this->extension->isApplicable($datagridConfiguration));
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'not orm datasource' => [
                'rootEntity' => \stdClass::class,
                'datasourceType' => ArrayDatasource::TYPE,
                'expectedResult' => false
            ],
            'orm datasource with not supported root entity' => [
                'rootEntity' => \stdClass::class,
                'datasourceType' => OrmDatasource::TYPE,
                'expectedResult' => false
            ],
            'orm datasource with EntityConfigModel root entity' => [
                'rootEntity' => EntityConfigModel::class,
                'datasourceType' => OrmDatasource::TYPE,
                'expectedResult' => true
            ],
            'orm datasource with FieldConfigModel root entity' => [
                'rootEntity' => FieldConfigModel::class,
                'datasourceType' => OrmDatasource::TYPE,
                'expectedResult' => true
            ],
        ];
    }

    /** @dataProvider visitResultsDataProvider */
    public function testVisitResult(
        string $rootEntity,
        array $configModelsData,
        array $resultObjectData,
        array $expectedResult
    ): void {
        $datagridConfiguration = DatagridConfiguration::create([]);
        $datagridConfiguration->setDatasourceType(OrmDatasource::TYPE);
        $datagridConfiguration
            ->getOrmQuery()
            ->addSelect('re')
            ->addFrom($rootEntity, 're');

        $result = ResultsObject::create([]);
        $result->setData($resultObjectData);

        $repository = $this->createMock(EntityRepository::class);

        $repository
            ->expects(self::exactly(count($resultObjectData)))
            ->method('find')
            ->withConsecutive(...array_map(static fn (array $result) => [$result['id']], $resultObjectData))
            ->willReturnCallback(function (int $id) use ($rootEntity, $configModelsData) {
                $models = $this->createModelsFromDataArray($rootEntity, $configModelsData);
                foreach ($models as $model) {
                    if ($model->getId() === $id) {
                        return $model;
                    }
                }

                return null;
            });

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with($rootEntity)
            ->willReturn($repository);

        $this->authorizationChecker
            ->expects(self::exactly(count($resultObjectData)))
            ->method('isGranted')
            ->willReturnCallback(function (string $attribute, ConfigModel $subject): bool {
                return $this->voter->vote($this->createMock(TokenInterface::class), $subject, [$attribute])
                    !== VoterInterface::ACCESS_DENIED;
            });

        $this->extension->visitResult($datagridConfiguration, $result);

        self::assertEquals($expectedResult, $result->getData());
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function visitResultsDataProvider(): array
    {
        return [
            'grid rows with manageable entities' => [
                'rootEntity' => EntityConfigModel::class,
                'configModelsData' => [
                    [
                        'id' => 1,
                        'values' => ['entity_management', ['enabled' => true]],
                        'class_name' => \stdClass::class
                    ],
                    [
                        'id' => 2,
                        'values' => ['entity_management', ['enabled' => false]],
                        'class_name' => \stdClass::class
                    ],
                    [
                        'id' => 3,
                        'values' => ['entity_management', ['enabled' => true]],
                        'class_name' => \stdClass::class
                    ]
                ],
                'resultObjectData' => [
                    [
                        'id' => 3,
                        'class_name' => \stdClass::class
                    ],
                    [
                        'id' => 1,
                        'class_name' => \stdClass::class
                    ],
                    [
                        'id' => 2,
                        'class_name' => \stdClass::class
                    ]
                ],
                'expectedResult' => [
                    [
                        'id' => 3,
                        'class_name' => \stdClass::class
                    ],
                    [
                        'id' => 1,
                        'class_name' => \stdClass::class
                    ],
                    [
                        'id' => 2,
                        'class_name' => \stdClass::class,
                        'update_link' => false,
                        'action_configuration' => ['update' => false]
                    ]
                ]
            ],
            'field config grid with fields owned by manageable entities' => [
                'rootEntity' => FieldConfigModel::class,
                'configModelsData' => [
                    [
                        'id' => 1,
                        'values' => ['entity_management', ['enabled' => true]],
                        'field_name' => 'testField'
                    ],
                    [
                        'id' => 2,
                        'values' => ['entity_management', ['enabled' => false]],
                        'field_name' => 'testField'
                    ],
                    [
                        'id' => 3,
                        'values' => ['entity_management', ['enabled' => true]],
                        'field_name' => 'testField'
                    ]
                ],
                'resultObjectData' => [
                    [
                        'id' => 3,
                        'field_name' => 'testField'
                    ],
                    [
                        'id' => 1,
                        'field_name' => 'testField'
                    ],
                    [
                        'id' => 2,
                        'field_name' => 'testField'
                    ]
                ],
                'expectedResult' => [
                    [
                        'id' => 3,
                        'field_name' => 'testField'
                    ],
                    [
                        'id' => 1,
                        'field_name' => 'testField'
                    ],
                    [
                        'id' => 2,
                        'field_name' => 'testField',
                        'update_link' => false,
                        'action_configuration' => ['update' => false]
                    ]
                ]
            ]
        ];
    }

    private function createModelsFromDataArray(string $entity, array $data): array
    {
        $result = [];
        foreach ($data as $entityData) {
            /** @var ConfigModel $configModel */
            $configModel = new $entity($entityData['class_name'] ?? $entityData['field_name']);
            ReflectionUtil::setId($configModel, $entityData['id']);

            if ($entity === FieldConfigModel::class) {
                $entityConfigModel = new EntityConfigModel(\stdClass::class);
                $entityConfigModel->setFields(new ArrayCollection([$configModel]));
                $entityConfigModel->fromArray(...$entityData['values']);
                $configModel->setEntity($entityConfigModel);
            } else {
                $configModel->fromArray(...$entityData['values']);
            }

            $result[] = $configModel;
        }

        return $result;
    }
}

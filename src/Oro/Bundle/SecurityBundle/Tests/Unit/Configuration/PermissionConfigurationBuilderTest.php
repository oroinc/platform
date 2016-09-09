<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;

class PermissionConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ValidatorInterface
     */
    protected $validator;

    /**
     * @var PermissionConfigurationBuilder
     */
    protected $builder;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with('OroSecurityBundle:PermissionEntity')
            ->willReturn($repository);

        $doctrineHelper->expects($this->any())
            ->method('isManageableEntityClass')
            ->willReturnMap([
                ['Entity1', true],
                ['Entity2', true],
                ['EntityNotManageable', false],
            ]);

        $this->validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');

        $this->builder = new PermissionConfigurationBuilder($doctrineHelper, $this->validator);
    }

    protected function tearDown()
    {
        unset($this->builder, $this->validator);
    }

    /**
     * @param array $expected
     * @param Permission $definition
     */
    protected function assertDefinitionConfiguration(array $expected, Permission $definition)
    {
        $this->assertSame($expected['label'], $definition->getLabel());
        $this->assertSame($expected['apply_to_all'], $definition->isApplyToAll());
        $this->assertSame($expected['group_names'], $definition->getGroupNames());
        $this->assertEquals($expected['exclude_entities'], $definition->getExcludeEntities());
        $this->assertEquals($expected['apply_to_entities'], $definition->getApplyToEntities());
        $this->assertSame($expected['description'], $definition->getDescription());
    }

    /**
     * @param array $configuration
     * @param array $expected
     * @dataProvider buildPermissionsDataProvider
     */
    public function testBuildPermissions(array $configuration, array $expected)
    {
        $this->validator->expects($this->any())
            ->method('validate')
            ->with($this->isInstanceOf('Oro\Bundle\SecurityBundle\Entity\Permission'))
            ->willReturn(new ConstraintViolationList());

        $permissions = $this->builder->buildPermissions($configuration);

        $this->assertSameSize($expected, $permissions);
        foreach ($permissions as $permission) {
            $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Entity\Permission', $permission);
            $this->assertArrayHasKey($permission->getName(), $expected);
            $this->assertDefinitionConfiguration($expected[$permission->getName()], $permission);
        }
    }

    public function testBuildPermissionsException()
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->isInstanceOf('Oro\Bundle\SecurityBundle\Entity\Permission'))
            ->willReturn(
                new ConstraintViolationList(
                    [
                        new ConstraintViolation('Test message', 'Test template', [], 'root', 'name', 'data')
                    ]
                )
            );

        $this->setExpectedException(
            '\Symfony\Component\Validator\Exception\ValidatorException',
            sprintf('Configuration of permission test_permission is invalid:%s    Test message', PHP_EOL)
        );

        $this->builder->buildPermissions(
            [
                'test_permission' => [
                    'name' => 'minimum_name',
                    'label' => 'My Label',
                ]
            ]
        );
    }

    /**
     * @return array
     */
    public function buildPermissionsDataProvider()
    {
        $permissionEntity1 = (new PermissionEntity())->setName('Entity1');
        $permissionEntity2 = (new PermissionEntity())->setName('Entity2');

        return [
            [
                'configuration' => [
                    'minimum_name' => [
                        'name' => 'minimum_name',
                        'label' => 'My Label',
                    ],
                    'maximum_name' => [
                        'name' => 'maximum_name',
                        'label' => 'My Label',
                        'apply_to_all' => false,
                        'group_names' => ['frontend', 'default'],
                        'exclude_entities' => [$permissionEntity1->getName(), $permissionEntity1->getName()],
                        'apply_to_entities' => [$permissionEntity2->getName(), $permissionEntity2->getName()],
                        'description' => 'Test description',
                    ],
                ],
                'expected' => [
                    'minimum_name' => [
                        'name' => 'minimum_name',
                        'label' => 'My Label',
                        'apply_to_all' => true,
                        'group_names' => [],
                        'exclude_entities' => new ArrayCollection(),
                        'apply_to_entities' => new ArrayCollection(),
                        'description' => '',
                    ],
                    'maximum_name' => [
                        'name' => 'maximum_name',
                        'label' => 'My Label',
                        'apply_to_all' => false,
                        'group_names' => ['frontend', 'default'],
                        'exclude_entities' => new ArrayCollection([$permissionEntity1]),
                        'apply_to_entities' => new ArrayCollection([$permissionEntity2]),
                        'description' => 'Test description',
                    ],
                ]
            ]
        ];
    }
}

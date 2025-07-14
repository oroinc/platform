<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Loader;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\Loader\AclAttributeLoader;
use Oro\Bundle\SecurityBundle\Attribute\Loader\AclConfigLoader;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeStorage;
use Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Fixtures\Controller\Classes as Controller;
use Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Fixtures\TestBundle;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\ResourcesContainer;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;
use PHPUnit\Framework\TestCase;

class AclAttributeLoadersTest extends TestCase
{
    private function getControllers(): array
    {
        $controllers = [
            [Controller\ClassWOAttribute::class, 'actionTest'],
            [Controller\ConfigController::class, 'testAction'],
            [Controller\ExtendedFromAbstractController::class, 'testAction'],
            [Controller\ExtendedController::class, 'test2Action'],
            [Controller\ExtendedController::class, 'test3Action'],
            [Controller\ExtendedController::class, 'test4Action'],
            [Controller\ExtendedController::class, 'test5Action'],
            [Controller\ExtendWithoutClassAttributeOverride::class, 'test2Action'],
            [Controller\ExtendWithoutClassAttributeOverride::class, 'test3Action'],
            [Controller\ExtendWithoutClassAttributeOverride::class, 'test4Action'],
            [Controller\ExtendWithoutClassAttributeOverride::class, 'test5Action'],
            [Controller\MainTestController::class, 'test1Action'],
            [Controller\MainTestController::class, 'test2Action'],
            [Controller\MainTestController::class, 'test3Action'],
            [Controller\MainTestController::class, 'test4Action'],
            [Controller\MainTestController::class, 'testNoAclAction']
        ];

        $result = [];
        foreach ($controllers as $controller) {
            $result[$controller[0] . '_' . $controller[1]] = $controller;
        }

        return $result;
    }

    public function testLoaders(): void
    {
        $controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $controllerClassProvider->expects(self::once())
            ->method('getControllers')
            ->willReturn($this->getControllers());

        $bundle = new TestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)]);

        $storage = new AclAttributeStorage();
        $resourcesContainer = new ResourcesContainer();
        $configLoader = new AclConfigLoader();
        $configLoader->load($storage, $resourcesContainer);
        $annotationReader = new AttributeReader();
        $annotationLoader = new AclAttributeLoader($controllerClassProvider, $annotationReader);
        $annotationLoader->load($storage, $resourcesContainer);

        self::assertFalse($storage->isKnownClass(Controller\ClassWOAttribute::class));
        self::assertFalse($storage->isKnownClass(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\CommentedClassController'
        ));

        self::assertAnnotations($storage->getAttributes());
        self::assertBindings($storage);
    }

    private static function assertBindings(AclAttributeStorage $storage)
    {
        self::assertEquals(
            [
                '!'           => 'user_test_main_controller',
                'test1Action' => 'user_test_main_controller_action1',
                'test2Action' => 'user_test_main_controller_action2',
                'test3Action' => 'user_test_main_controller_action2',
                'test4Action' => 'user_test_main_controller_action4',
            ],
            $storage->getBindings(Controller\MainTestController::class)
        );
        self::assertEquals(
            [
                '!'           => 'user_test_extended_controller',
                'test3Action' => 'user_test_main_controller_action1',
                'test4Action' => 'user_test_main_controller_action4_rewrite',
                'test5Action' => 'user_test_main_controller_action5',
                'test1Action' => 'user_test_main_controller_action1',
            ],
            $storage->getBindings(Controller\ExtendedController::class)
        );
        self::assertEquals(
            [
                'test3Action' => 'user_test_main_controller_action1',
                'test4Action' => 'user_test_main_controller_action4_rewrite',
                'test5Action' => 'user_test_main_controller_action5',
                'test1Action' => 'test_controller',
            ],
            $storage->getBindings(Controller\ExtendWithoutClassAttributeOverride::class)
        );
        self::assertEquals(
            ['testAction' => 'test_controller'],
            $storage->getBindings(Controller\ConfigController::class)
        );
        self::assertEquals(
            ['testAction' => 'user_action_in_abstract_controller'],
            $storage->getBindings(Controller\AbstractController::class)
        );
        self::assertEquals(
            ['testAction' => 'user_action_in_abstract_controller'],
            $storage->getBindings(Controller\ExtendedFromAbstractController::class)
        );
    }

    /**
     * @param Acl[] $annotations
     */
    private static function assertAnnotations(array $annotations)
    {
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'test_controller',
                'type'       => 'entity',
                'class'      => 'AcmeBundle\Entity\SomeEntity',
                'permission' => 'VIEW',
                'group_name' => 'Test Group',
                'label'      => 'Test controller'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'test_wo_bindings',
                'type'       => 'action',
                'group_name' => 'Another Group',
                'label'      => 'Test without bindings'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_action_in_abstract_controller',
                'type'       => 'entity',
                'class'      => 'AcmeBundle\Entity\SomeClass',
                'permission' => 'VIEW',
                'group_name' => 'Test Group',
                'label'      => 'Action In Abstract Controller'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_test_main_controller',
                'type'       => 'action',
                'group_name' => 'Test Group',
                'label'      => 'Test controller for ACL'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_test_main_controller_action1',
                'type'       => 'entity',
                'class'      => 'AcmeBundle\Entity\SomeClass',
                'permission' => 'VIEW',
                'group_name' => 'Test Group',
                'label'      => 'Action 1'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_test_main_controller_action2',
                'type'       => 'action',
                'group_name' => 'Another Group',
                'label'      => 'Action 2'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_test_main_controller_action4',
                'type'       => 'action',
                'group_name' => 'Another Group',
                'label'      => 'Action 4'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_test_extended_controller',
                'type'       => 'action',
                'group_name' => 'Test Group',
                'label'      => 'Extended test controller for ACL'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_test_main_controller_action4_rewrite',
                'type'       => 'action',
                'group_name' => 'Another Group',
                'label'      => 'Action 4 Rewrite'
            ]),
            $annotations
        );
        self::assertHasAnnotation(
            Acl::fromArray([
                'id'         => 'user_test_main_controller_action5',
                'type'       => 'action',
                'group_name' => 'Another Group',
                'label'      => 'Action 5'
            ]),
            $annotations
        );
    }

    /**
     * @param Acl   $annotation
     * @param Acl[] $annotations
     */
    private static function assertHasAnnotation(Acl $annotation, array $annotations)
    {
        self::assertTrue(in_array($annotation, $annotations), $annotation->getId());
    }
}

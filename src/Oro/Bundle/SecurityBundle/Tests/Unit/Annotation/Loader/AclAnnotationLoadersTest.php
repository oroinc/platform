<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoader;
use Oro\Bundle\SecurityBundle\Annotation\Loader\AclConfigLoader;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\AbstractController;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\ClassWOAnnotation;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\ConfigController;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\ExtendedController;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\ExtendedFromAbstractController;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\ExtendWithoutClassAnnotationOverride;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes\MainTestController;
use Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\TestBundle;
use Oro\Component\Config\CumulativeResourceManager;

class AclAnnotationLoadersTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Annotations\Reader')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }
    }

    public function testLoaders()
    {
        $bundle = new TestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)]);

        $storage = new AclAnnotationStorage();
        $configLoader = new AclConfigLoader();
        $configLoader->load($storage);
        $annotationLoader = new AclAnnotationLoader(new AnnotationReader());
        $annotationLoader->load($storage);

        $this->assertFalse($storage->isKnownClass('ClassWONamespace'));
        $this->assertFalse($storage->isKnownClass(ClassWOAnnotation::class));
        $this->assertFalse($storage->isKnownClass('Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures'
            . '\Controller\Classes\CommentedClassController'));

        $this->assertAnnotations($storage->getAnnotations());
        $this->assertEquals(
            [
                '!' => 'user_test_main_controller',
                'test1Action' => 'user_test_main_controller_action1',
                'test2Action' => 'user_test_main_controller_action2',
                'test3Action' => 'user_test_main_controller_action2',
                'test4Action' => 'user_test_main_controller_action4',

            ],
            $storage->getBindings(MainTestController::class)
        );
        $this->assertEquals(
            [
                '!' => 'user_test_extended_controller',
                'test3Action' => 'user_test_main_controller_action1',
                'test4Action' => 'user_test_main_controller_action4_rewrite',
                'test5Action' => 'user_test_main_controller_action5',
                'test1Action' => 'user_test_main_controller_action1',

            ],
            $storage->getBindings(ExtendedController::class)
        );
        $this->assertEquals(
            [
                'test3Action' => 'user_test_main_controller_action1',
                'test4Action' => 'user_test_main_controller_action4_rewrite',
                'test5Action' => 'user_test_main_controller_action5',
                'test1Action' => 'test_controller',

            ],
            $storage->getBindings(ExtendWithoutClassAnnotationOverride::class)
        );
        $this->assertEquals(
            ['testAction' => 'test_controller'],
            $storage->getBindings(ConfigController::class)
        );
        $this->assertEquals(
            ['testAction' => 'user_action_in_abstract_controller'],
            $storage->getBindings(AbstractController::class)
        );
        $this->assertEquals(
            ['testAction' => 'user_action_in_abstract_controller'],
            $storage->getBindings(ExtendedFromAbstractController::class)
        );
    }

    private function assertAnnotations($annotations)
    {
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'test_controller',
                'type' => 'entity',
                'class' => 'AcmeBundle\Entity\SomeEntity',
                'permission' => 'VIEW',
                'group_name' => 'Test Group',
                'label' => 'Test controller'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'test_wo_bindings',
                'type' => 'action',
                'group_name' => 'Another Group',
                'label' => 'Test without bindings'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_action_in_abstract_controller',
                'type' => 'entity',
                'class' => 'AcmeBundle\Entity\SomeClass',
                'permission' => 'VIEW',
                'group_name' => 'Test Group',
                'label' => 'Action In Abstract Controller'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_test_main_controller',
                'type' => 'action',
                'group_name' => 'Test Group',
                'label' => 'Test controller for ACL'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_test_main_controller_action1',
                'type' => 'entity',
                'class' => 'AcmeBundle\Entity\SomeClass',
                'permission' => 'VIEW',
                'group_name' => 'Test Group',
                'label' => 'Action 1'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_test_main_controller_action2',
                'type' => 'action',
                'group_name' => 'Another Group',
                'label' => 'Action 2'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_test_main_controller_action4',
                'type' => 'action',
                'group_name' => 'Another Group',
                'label' => 'Action 4'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_test_extended_controller',
                'type' => 'action',
                'group_name' => 'Test Group',
                'label' => 'Extended test controller for ACL'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_test_main_controller_action4_rewrite',
                'type' => 'action',
                'group_name' => 'Another Group',
                'label' => 'Action 4 Rewrite'
            ]),
            $annotations
        ));
        $this->assertTrue(in_array(
            new Acl([
                'id' => 'user_test_main_controller_action5',
                'type' => 'action',
                'group_name' => 'Another Group',
                'label' => 'Action 5'
            ]),
            $annotations
        ));
    }
}

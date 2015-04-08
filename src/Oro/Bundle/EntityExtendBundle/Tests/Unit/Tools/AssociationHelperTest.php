<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\AssociationHelper;

class AssociationHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getMethodNamesProvider
     */
    public function testMethodNames($method, $type, $methodName)
    {
        $this->assertEquals(
            $methodName,
            AssociationHelper::$method($type)
        );
    }

    public static function getMethodNamesProvider()
    {
        return [
            ['getManyToOneSupportMethodName', null, 'supportTarget'],
            ['getManyToOneGetterMethodName', null, 'getTarget'],
            ['getManyToOneSetterMethodName', null, 'setTarget'],
            ['getManyToOneResetMethodName', null, 'resetTargets'],
            ['getManyToOneGetTargetEntitiesMethodName', null,'getTargetEntities'],
            ['getMultipleManyToOneSupportMethodName', 'test_type', 'supportTestTypeTarget'],
            ['getMultipleManyToOneGetterMethodName', 'test_type', 'getTestTypeTargets'],
            ['getMultipleManyToOneSetterMethodName', 'test_type', 'addTestTypeTarget'],
            ['getMultipleManyToOneResetMethodName', 'test_type', 'resetTestTypeTargets'],
            ['getManyToManySupportMethodName', 'test_type', 'supportTestTypeTarget'],
            ['getManyToManyGetterMethodName', 'test_type', 'getTestTypeTargets'],
            ['getManyToManySetterMethodName', 'test_type', 'addTestTypeTarget'],
            ['getManyToManyHasMethodName', 'test_type', 'hasTestTypeTarget'],
            ['getManyToManyRemoveMethodName', 'test_type', 'removeTestTypeTarget'],
            ['getManyToManyGetTargetEntitiesMethodName', 'test_type','getTestTypeTargetEntities'],
        ];
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;

class AssociationNameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getMethodNamesProvider
     */
    public function testGenerateMethodNames($method, $kind, $methodName)
    {
        $this->assertEquals(
            $methodName,
            AssociationNameGenerator::$method($kind)
        );
    }

    public static function getMethodNamesProvider()
    {
        return [
            ['generateSupportTargetMethodName', null, 'supportTarget'],
            ['generateGetTargetMethodName', null, 'getTarget'],
            ['generateGetTargetsMethodName', null, 'getTargets'],
            ['generateHasTargetMethodName', null, 'hasTarget'],
            ['generateSetTargetMethodName', null, 'setTarget'],
            ['generateResetTargetsMethodName', null,'resetTargets'],
            ['generateAddTargetMethodName', null, 'addTarget'],
            ['generateRemoveTargetMethodName', null,'removeTarget'],
            ['generateSupportTargetMethodName', 'test_type', 'supportTestTypeTarget'],
            ['generateGetTargetMethodName', 'test_type', 'getTestTypeTarget'],
            ['generateGetTargetsMethodName', 'test_type', 'getTestTypeTargets'],
            ['generateHasTargetMethodName', 'test_type', 'hasTestTypeTarget'],
            ['generateSetTargetMethodName', 'test_type', 'setTestTypeTarget'],
            ['generateResetTargetsMethodName', 'test_type', 'resetTestTypeTargets'],
            ['generateAddTargetMethodName', 'test_type', 'addTestTypeTarget'],
            ['generateRemoveTargetMethodName', 'test_type', 'removeTestTypeTarget'],
        ];
    }
}

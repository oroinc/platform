<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;
use PHPUnit\Framework\TestCase;

class AssociationNameGeneratorTest extends TestCase
{
    /**
     * @dataProvider getMethodNamesProvider
     */
    public function testGenerateMethodNames($method, $kind, $methodName): void
    {
        $this->assertEquals(
            $methodName,
            AssociationNameGenerator::$method($kind)
        );
    }

    public static function getMethodNamesProvider(): array
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

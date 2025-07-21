<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Helper\ConfigModelConstraintsHelper;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ConfigModelAwareConstraintInterface;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\EnumValuesUnique;
use PHPUnit\Framework\TestCase;

class ConfigModelConstraintsHelperTest extends TestCase
{
    public function testConfigureConstraintsWithConfigModel(): void
    {
        $constraints = [
            [EnumValuesUnique::class => null],
            [ConfigModelAwareConstraintInterface::class => null]
        ];

        $configModel = $this->createMock(ConfigModel::class);
        $constraints = ConfigModelConstraintsHelper::configureConstraintsWithConfigModel($constraints, $configModel);

        self::assertEquals(
            [
                [EnumValuesUnique::class => null],
                [ConfigModelAwareConstraintInterface::class => ['configModel' => $configModel]]
            ],
            $constraints
        );
    }
}

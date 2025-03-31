<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Validator;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Validator\CustomEntityConfigValidatorService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CustomEntityConfigValidatorServiceTest extends WebTestCase
{
    private CustomEntityConfigValidatorService $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->validator = self::getContainer()->get('oro_entity_extend.validator.custom_entity_config_validator');
    }

    public function testCheckConfigs(): void
    {
        $result = $this->validator->checkConfigs();

        // no errors with custom entities configuration
        $this->assertNull($result);
    }

    public function testCheckConfigExists(): void
    {
        $configuredCustomEntity = ExtendHelper::ENTITY_NAMESPACE . 'TestEntity1';
        $result = $this->validator->checkConfigExists($configuredCustomEntity);

        $this->assertNull($result);
    }

    public function testCheckConfigDoesNotExists(): void
    {
        $isNotConfiguredCustomEntity = ExtendHelper::ENTITY_NAMESPACE . 'IsNotConfiguredCustomEntity';
        $message = 'Custom Entity is not configured properly. '
            . 'Please update your `oro_entity_extend.custom_entities` configuration.'
            . "\n"
            . 'List of missing custom entities:'
            . "\n"
            . '  - '
            . $isNotConfiguredCustomEntity;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->validator->checkConfigExists($isNotConfiguredCustomEntity);
    }
}

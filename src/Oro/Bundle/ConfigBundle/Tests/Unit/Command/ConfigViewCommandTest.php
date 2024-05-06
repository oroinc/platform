<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Command;

use Oro\Bundle\ConfigBundle\Command\ConfigViewCommand;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\SalesBundle\Form\Type\OpportunityStatusConfigType;
use Oro\Component\Testing\Command\CommandTestingTrait;
use PHPUnit\Framework\TestCase;

class ConfigViewCommandTest extends TestCase
{
    use CommandTestingTrait;

    private ConfigViewCommand $command;

    protected function setUp(): void
    {
        $configManager = $this->createMock(ConfigManager::class, []);
        $configManager->method('get')->will(
            $this->returnCallback(function ($fieldName) {
                $configValues = [
                    // Plain values
                    'oro_frontend.web_api' => true,
                    'oro_locale.default_localization' => 1,
                    'oro_sales.opportunity_statuses' => null,
                    'oro_website.secure_url' => 'https://example.com',
                    'oro_locale.enabled_localizations' => [1, 2, 3],
                    'oro_example.dummy_object' => (object)['test' => 'value'],

                    // Encrypted value
                    'oro_example.secret_value' => 'Shh, keep it secret',

                    // Nonsense value
                    'oro_example.nonsense_value' => fopen('php://stdin', 'r'),
                ];
                return $configValues[$fieldName] ?? null;
            })
        );

        $encryptedField = $this->createConfiguredMock(FieldNodeDefinition::class, [
            'getName' => 'oro_example.secret_value',
            'getType' => OroEncodedPlaceholderPasswordType::class,
        ]);

        $nullField = $this->createConfiguredMock(FieldNodeDefinition::class, [
            'getName' => 'oro_sales.opportunity_statuses',
            'getType' => OpportunityStatusConfigType::class,
        ]);

        $fieldGroup = $this->createConfiguredMock(GroupNodeDefinition::class, [
            'getIterator' => new \ArrayIterator([
                $encryptedField,
                $nullField,
            ]),
        ]);

        $formProvider = $this->createConfiguredMock(SystemConfigurationFormProvider::class, [
            'getTree' => $fieldGroup,
        ]);

        $this->command = new ConfigViewCommand(
            $configManager,
            $formProvider
        );
    }

    private function validateConfigView(string $configFieldName, string $expectedValue): void
    {
        $commandTester = $this->doExecuteCommand($this->command, ['name' => $configFieldName]);
        $this->assertOutputContains($commandTester, $expectedValue);
    }

    public function testViewScalarValues(): void
    {
        $this->validateConfigView('oro_frontend.web_api', 'true');
        $this->validateConfigView('oro_locale.default_localization', '1');
        $this->validateConfigView('oro_sales.opportunity_statuses', 'null');
        $this->validateConfigView('oro_website.secure_url', 'https://example.com');
    }

    public function testViewArrayValue(): void
    {
        $this->validateConfigView('oro_locale.enabled_localizations', '[ 1, 2, 3 ]');
    }

    public function testViewObjectValue(): void
    {
        $this->validateConfigView('oro_example.dummy_object', '{ "test": "value" }');
    }

    public function testViewEncryptedValue(): void
    {
        $this->assertProducedError(
            $this->doExecuteCommand($this->command, ['name' => 'oro_example.secret_value']),
            "Encrypted value"
        );
    }

    public function testViewInvalidValue(): void
    {
        $this->assertProducedError(
            $this->doExecuteCommand($this->command, ['name' => 'oro_example.nonsense_value']),
            "Value cannot be displayed"
        );
    }

    public function testViewNonexistentValue(): void
    {
        $this->assertProducedError(
            $this->doExecuteCommand($this->command, ['name' => 'oro_example.nonexistent_field']),
            "Unknown config field"
        );
    }
}

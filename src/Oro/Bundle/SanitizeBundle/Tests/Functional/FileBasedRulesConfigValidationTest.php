<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional;

use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity\TestSanitizable;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\EntityAllMetadataProviderDecorator;
use Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\Rule\FileBasedProviderDecorator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * @dbIsolationPerTest
 */
class FileBasedRulesConfigValidationTest extends WebTestCase
{
    private FileBasedProviderDecorator $fileBaseRulesProvider;

    #[\Override]
    protected function setup(): void
    {
        $this->initClient();

        $metatdataProvider = $this->getContainer()->get(EntityAllMetadataProviderDecorator::class);
        $metatdataProvider->setEntitiesToFilter([TestSanitizable::class]);
        $this->fileBaseRulesProvider = $this->getContainer()->get(FileBasedProviderDecorator::class);
    }

    /**
     * @dataProvider wrongRulesConfigurationProvider
     */
    public function testWrongRulesConfiguration(array $ruleConfigFiles, string $expectedError)
    {
        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage($expectedError);

        $this->fileBaseRulesProvider->setRuleFiles($ruleConfigFiles);
        $this->fileBaseRulesProvider->getRawSqls();
    }

    public function wrongRulesConfigurationProvider(): array
    {
        return [
            'raw_sql_wrong_type' => [
                ['invalid/raw_sqls_wrong_type.yml'],
                'Invalid type for path "oro_sanitize.raw_sqls". Expected "array", but got "string"'
            ],
            'raw_sql_wrong_type_after_valid_file' => [
                ['valid/single_file_config_1.out', 'invalid/raw_sqls_wrong_type.yml'],
                'Invalid type for path "oro_sanitize.raw_sqls". Expected "array", but got "string"'
            ],
            'entity_raw_sqls_wrong_type' => [
                ['invalid/entity_raw_sqls_wrong_type.yml'],
                'Invalid type for path "oro_sanitize.entity.test_sanitizable_entity.raw_sqls". '
                . 'Expected "array", but got "string"'
            ],
            'field_raw_sqls_wrong_type' => [
                ['invalid/field_raw_sqls_wrong_type.yml'],
                'Invalid type for path "oro_sanitize.entity.test_sanitizable_entity.fields.phone.raw_sqls". '
                . 'Expected "array", but got "string"'
            ],
            'undefined_entity_rule' => [
                ['invalid/undefined_entity_rule.yml'],
                'The value "__undefined_rule_alias__" is not allowed for path '
                . '"oro_sanitize.entity.test_sanitizable_entity.rule"'
            ],
            'undefined_field_rule' => [
                ['invalid/undefined_field_rule.yml'],
                'The value "__undefined_rule_alias__" is not allowed for path '
                . '"oro_sanitize.entity.test_sanitizable_entity.fields.phone.rule'
            ],
            'entity_rule_options_wrong_type' => [
                ['invalid/entity_rule_options_wrong_type.yml'],
                'Invalid type for path "oro_sanitize.entity.test_sanitizable_entity.rule_options". '
                . 'Expected "array", but got "string'
            ],
            'field_rule_options_wrong_type' => [
                ['invalid/field_rule_options_wrong_type.yml'],
                'Invalid type for path "oro_sanitize.entity.test_sanitizable_entity.fields.phone.rule_options". '
                . 'Expected "array", but got "string"'
            ],
            'wrong_top_level_key' => [
                ['invalid/wrong_top_level_key.yml'],
                'Unrecognized option "__wrong_key" under "oro_sanitize". Available options are "entity", "raw_sqls'
            ],
            'wrong_entity_level_key' => [
                ['invalid/wrong_entity_level_key.yml'],
                'Unrecognized option "__wrong_key" under "oro_sanitize.entity.test_sanitizable_entity". '
                . 'Available options are "fields", "raw_sqls", "rule", "rule_options'
            ],
            'wrong_feld_level_key' => [
                ['invalid/wrong_feld_level_key.yml'],
                'Unrecognized option "__wrong_key" under "oro_sanitize.entity.test_sanitizable_entity.fields.phone". '
                . 'Available options are "raw_sqls", "rule", "rule_options'
            ],
            'entity_empty_options' => [
                ['invalid/entity_empty_options.yml'],
                'Invalid configuration for path "oro_sanitize.entity.test_sanitizable_entity": '
                . "At least one of the following options must be pointed: 'rule', 'raw_sqls', 'fields'"
            ],
            'field_empty_options' => [
                ['invalid/field_empty_options.yml'],
                'Invalid configuration for path "oro_sanitize.entity.test_sanitizable_entity.fields.phone": '
                . "At least one of the following options must be pointed: 'rule', 'raw_sqls'"
            ],
        ];
    }

    public function testUnboundRulesDetection(): void
    {
        $this->fileBaseRulesProvider->setRuleFiles(['invalid/unbound_rules.yml']);
        self::assertEquals(
            [
                "Reference in the sanitizing rule to a non-existing entity class or table name 'non_existing_entity'",
                "Reference in the sanitizing rule to a non-existing field or column name 'non_existing_field'"
                . " of entity class or table name "
                . "'Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity\TestSanitizable'"
            ],
            $this->fileBaseRulesProvider->getUnboundRuleMessages()
        );
    }
}

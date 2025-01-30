<?php

namespace Oro\Bundle\SanitizeBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SanitizeBundle\Provider\EntityAllMetadataProvider;
use Oro\Bundle\SanitizeBundle\Provider\Rule\FileBasedProvider;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Entity\ProcessorsRegistry as EntityRuleProcessorRegistry;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser\AlwaysNullGuesser;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser\GuesserInterface;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\JsonBuildPairsPostProcessor;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorsRegistry as FieldRuleProcessorRegistry;

/**
 * Sanitize SQLs loader. SQLs are built and collected based on rules,
 * field configurations of entities, and dedicated files.
 */
class SanitizeSqlLoader
{
    private const EXTEND_CONFIG_SCOPE = 'extend';
    private const SANITIZE_CONFIG_SCOPE = 'sanitize';

    private AlwaysNullGuesser $alwayNullProcessorGuesser;
    private ?GuesserInterface $usedProcessorGuesser = null;

    private array $issueMessages = [];

    public function __construct(
        private EntityAllMetadataProvider $metadataProvider,
        private ConfigManager $configManager,
        private GuesserInterface $processorGuesser,
        private EntityRuleProcessorRegistry $entityProcessorRegistry,
        private FieldRuleProcessorRegistry $fieldProcessorRegistry,
        private JsonBuildPairsPostProcessor $jsonBuildPairsPostProcessor,
        private FileBasedProvider $sanitizeRulesProvider
    ) {
        $this->alwayNullProcessorGuesser = new AlwaysNullGuesser();
    }

    public function setConfigConnactionName(?string $configConnectionName = null): void
    {
        $this->configConnectionName = $configConnectionName;
    }

    public function load(bool $useProccesorGuessing = true): array
    {
        $this->usedProcessorGuesser = $useProccesorGuessing
            ? $this->processorGuesser
            : $this->alwayNullProcessorGuesser;

        $this->issueMessages = [$this->sanitizeRulesProvider->getUnboundRuleMessages()];
        $sqls = [];

        $sqls[] = $this->sanitizeRulesProvider->getRawSqls();
        foreach ($this->metadataProvider->getAllMetadata() as $metadata) {
            $sqls[] = $this->getEntitySantizedSqls($metadata);
        }
        $sqls[] = $this->jsonBuildPairsPostProcessor->getSqls();

        $this->issueMessages = array_merge(...$this->issueMessages);

        return array_unique(array_merge(...$sqls));
    }

    public function getLastIssueMessages(): array
    {
        return $this->issueMessages;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getEntitySantizedSqls(ClassMetadata $metadata): array
    {
        $className = $metadata->getName();
        $classReflaction = new \ReflectionClass($className);
        if ($classReflaction->isAbstract()) {
            return [];
        }

        $sqls = [];

        $entityHasConfig = $this->configManager->hasConfig($className);
        $santizeConfig = $entityHasConfig
            ? $this->configManager->getEntityConfig(self::SANITIZE_CONFIG_SCOPE, $className)->getValues()
            : [];

        // Sanitizing configuration read from the entity configuration
        // has priority over the configuration read from files
        if (!empty($santizeConfig['rule']) || !empty($santizeConfig['raw_sqls'])) {
            $sanitizeRule = $santizeConfig['rule'] ?? '';
            $sanitizeRuleOptions = $santizeConfig['rule_options'] ?? [];
            $sqls[] = $santizeConfig['raw_sqls'] ?? [];
        } else {
            $sanitizeRule = $this->sanitizeRulesProvider->getEntitySanitizeRule($metadata);
            $sanitizeRuleOptions = $this->sanitizeRulesProvider->getEntitySanitizeRuleOptions($metadata);
            $sqls[] = $this->sanitizeRulesProvider->getEntitySanitizeRawSqls($metadata);
        }

        if (!empty($sanitizeRule)) {
            $sanitizeProcessor = $this->entityProcessorRegistry->get($sanitizeRule);
            $sqls[] = $sanitizeProcessor->getSqls($metadata, $sanitizeRuleOptions);
        }

        $fieldConfigs = $entityHasConfig
            ? $this->configManager->getConfigs(self::EXTEND_CONFIG_SCOPE, $className, true)
            : [];
        foreach ($fieldConfigs as $fieldConfig) {
            if ($fieldConfig->get('is_serialized')) {
                $fieldConfigId = $fieldConfig->getId();
                $sqls[] = $this->getEntityFieldSantizedSqls($fieldConfigId->getFieldName(), $metadata, true);
            }
        }

        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $sqls[] = $this->getEntityFieldSantizedSqls($fieldName, $metadata);
        }

        return array_merge(...$sqls);
    }

    private function getEntityFieldSantizedSqls(
        string $fieldName,
        ClassMetadata $metadata,
        bool $isSerialized = false
    ): array {
        $sqls = [];

        $className = $metadata->getName();
        $santizeConfig = $this->configManager->hasConfig($className, $fieldName)
            ? $this->configManager->getFieldConfig(self::SANITIZE_CONFIG_SCOPE, $className, $fieldName)->getValues()
            : [];

        // Sanitizing configuration read from the entity field configuration
        // has priority over the configuration read from files
        $useSanitizeRuleGuesser = true;
        if (!empty($santizeConfig['rule']) || !empty($santizeConfig['raw_sqls'])) {
            $sanitizeRule = $santizeConfig['rule'] ?? '';
            $sanitizeRuleOptions = $santizeConfig['rule_options'] ?? [];
            $sqls[] = $santizeConfig['raw_sqls'] ?? [];
            $useSanitizeRuleGuesser = false;
        } else {
            $sanitizeRule = $this->sanitizeRulesProvider->getFieldSanitizeRule($fieldName, $metadata);
            $sanitizeRuleOptions = $this->sanitizeRulesProvider->getFieldSanitizeRuleOptions($fieldName, $metadata);
            $fileReadSqls = $this->sanitizeRulesProvider->getFieldSanitizeRawSqls($fieldName, $metadata);
            $sqls[] = $fileReadSqls;
            $useSanitizeRuleGuesser = !count($fileReadSqls);
        }

        $sanitizeProcessor = null;
        if (!empty($sanitizeRule)) {
            $sanitizeProcessor = $this->fieldProcessorRegistry->get($sanitizeRule);
        } elseif ($useSanitizeRuleGuesser) {
            $sanitizeProcessor = $this->usedProcessorGuesser->guessProcessor($fieldName, $metadata, $isSerialized);
            $sanitizeRuleOptions = [];
        }

        if (null !== $sanitizeProcessor) {
            $this->issueMessages[] = $sanitizeProcessor->getIncompatibilityMessages(
                $fieldName,
                $metadata,
                $sanitizeRuleOptions
            );

            if ($isSerialized) {
                $sanitizeProcessor->prepareSerializedFieldUpdate($fieldName, $metadata, $sanitizeRuleOptions);
            } else {
                $sqls[] = $sanitizeProcessor->getSqls($fieldName, $metadata, $sanitizeRuleOptions);
            }
        }

        return array_merge(...$sqls);
    }
}

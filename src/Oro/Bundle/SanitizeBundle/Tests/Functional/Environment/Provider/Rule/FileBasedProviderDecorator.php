<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider\Rule;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SanitizeBundle\OroSanitizeBundle;
use Oro\Bundle\SanitizeBundle\Provider\EntityAllMetadataProvider;
use Oro\Bundle\SanitizeBundle\Provider\Rule\FileBasedConfiguration;
use Oro\Bundle\SanitizeBundle\Provider\Rule\FileBasedProvider;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class FileBasedProviderDecorator extends FileBasedProvider
{
    private ?array $ruleFiles = null;
    private string $fixturesDir;

    public function __construct(
        EntityAllMetadataProvider $metadataProvider,
        ConfigManager $configMAnager,
        FileBasedConfiguration $sanitizeConfiguration
    ) {
        parent::__construct($metadataProvider, $configMAnager, $sanitizeConfiguration);

        $this->fixturesDir = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            __DIR__ . '/../../../DataFixtures/sanitize_config/'
        );
    }

    public function setRuleFiles(array $ruleFiles = null): void
    {
        if (null !== $ruleFiles) {
            $this->ruleFiles = array_map(function ($file) {
                return trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($file)), DIRECTORY_SEPARATOR);
            }, $ruleFiles);
        } else {
            $this->ruleFiles = null;
        }
    }

    public function setFixturesDir(string $fixturesDir): void
    {
        $this->fixturesDir =
            rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($fixturesDir)), DIRECTORY_SEPARATOR);
    }

    #[\Override]
    protected function comulativeSaniztizeConfigLoad(): array
    {
        if (null === $this->ruleFiles) {
            return parent::comulativeSaniztizeConfigLoad();
        }

        $resources = [];

        foreach ($this->ruleFiles as $ruleFile) {
            $loader = new YamlCumulativeFileLoader($ruleFile);
            $resources[] = $loader->load(OroSanitizeBundle::class, $this->fixturesDir);
        }

        return $resources;
    }
}

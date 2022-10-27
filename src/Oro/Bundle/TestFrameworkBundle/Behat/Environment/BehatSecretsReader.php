<?php
declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Behat\Environment;

use Oro\Bundle\TestFrameworkBundle\Exception\BehatSecretsReaderException;
use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * Behat secrets configuration processor
 */
class BehatSecretsReader
{
    private const CONFIG_FILENAME = '.behat-secrets.yml';

    private static ?BehatSecretsReader $instance      = null;
    private array                      $configuration = [];

    /**
     * @return static
     * @throws BehatSecretsReaderException
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @throws BehatSecretsReaderException
     */
    public function __construct()
    {
        if (!defined('BEHAT_BIN_PATH')) {
            throw new BehatSecretsReaderException('Behat path constant is not defined');
        }

        $rootDir = dirname(BEHAT_BIN_PATH, 5);
        $file    = $rootDir . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

        if (!is_readable($file)) {
            $message = sprintf('Behat secrets configuration file %s is not readable.', self::CONFIG_FILENAME);
            throw new BehatSecretsReaderException($message);
        }

        $parser = new YamlParser();
        $config = $parser->parseFile($file);

        if (empty($config['secrets']) || !is_array($config['secrets'])) {
            $message = sprintf('Behat secrets configuration %s is not valid.', self::CONFIG_FILENAME);
            throw new BehatSecretsReaderException($message);
        }

        $this->configuration = $config['secrets'];
    }

    /**
     * @param string $propertyPath
     * @return string|null
     * @throws BehatSecretsReaderException
     */
    public function getValue(string $propertyPath): ?string
    {
        try {
            $paths = explode('.', $propertyPath);
            $value = $this->configuration;
            foreach ($paths as $path) {
                if (!array_key_exists($path, $value)) {
                    throw \Exception();
                }
                $value = $value[$path];
            }

            if (!is_scalar($value)) {
                throw \Exception();
            }
        } catch (\Throwable $e) {
            $message = sprintf(
                'Cannot read secrets variable %s in %s file',
                $propertyPath,
                self::CONFIG_FILENAME
            );
            throw new BehatSecretsReaderException($message);
        }

        return (string)$value;
    }
}

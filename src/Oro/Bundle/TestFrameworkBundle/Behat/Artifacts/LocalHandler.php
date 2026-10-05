<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\TokenGenerator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Saves test artifacts to the local filesystem
 */
class LocalHandler implements ArtifactsFileHandlerInterface
{
    private string $directory;
    private ?string $baseUrl;

    public function __construct(array $config)
    {
        $this->directory = rtrim($config['directory'], DIRECTORY_SEPARATOR);
        // An empty base URL becomes null, so saveFile() returns a file:// path and not "/<name>".
        $baseUrl = $config['base_url'] ? trim($config['base_url'], " \t\n\r\0\x0B\\") : '';
        $this->baseUrl = '' !== $baseUrl ? rtrim($baseUrl, '/') . '/' : null;
        $filesystem = new Filesystem();
        if ($config['auto_clear']) {
            $filesystem->remove($this->directory);
        }

        if (!$filesystem->exists($this->directory)) {
            $filesystem->mkdir($this->directory, 0777);
        }
    }

    #[\Override]
    public function save($file)
    {
        return $this->saveFile($file, TokenGenerator::generateToken('image') . '.png');
    }

    #[\Override]
    public function saveFile(string $content, string $fileName): string
    {
        $fileName = basename($fileName);
        $filePath = $this->directory . DIRECTORY_SEPARATOR . $fileName;

        file_put_contents($filePath, $content);

        if ($this->baseUrl) {
            return $this->baseUrl . $fileName;
        }

        return 'file://' . $filePath;
    }

    #[\Override]
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    #[\Override]
    public static function getConfigKey()
    {
        return 'local';
    }
}

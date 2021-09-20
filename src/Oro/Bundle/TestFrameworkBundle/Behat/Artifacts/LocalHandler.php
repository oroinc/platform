<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\TokenGenerator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Saves test artifacts to the local filesystem
 */
class LocalHandler implements ArtifactsHandlerInterface
{
    private string $directory;
    private ?string $baseUrl;

    public function __construct(array $config)
    {
        $this->directory = rtrim($config['directory'], DIRECTORY_SEPARATOR);
        $this->baseUrl = $config['base_url'] ? trim($config['base_url'], " \t\n\r\0\x0B\\") : null;
        $this->baseUrl = rtrim($this->baseUrl, '/').'/';
        $filesystem = new Filesystem();
        if ($config['auto_clear']) {
            $filesystem->remove($this->directory);
        }

        if (!$filesystem->exists($this->directory)) {
            $filesystem->mkdir($this->directory, 0777);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($file)
    {
        $fileName = TokenGenerator::generateToken('image').'.png';
        $screenshotName = $this->directory.DIRECTORY_SEPARATOR.$fileName;

        file_put_contents($screenshotName, $file);

        if ($this->baseUrl) {
            return $this->baseUrl.$fileName;
        }

        return 'file://'.$screenshotName;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigKey()
    {
        return 'local';
    }
}

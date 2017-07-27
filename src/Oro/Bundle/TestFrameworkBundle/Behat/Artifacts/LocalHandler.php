<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\TokenGenerator;
use Symfony\Component\Filesystem\Filesystem;

class LocalHandler implements ArtifactsHandlerInterface
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var bool
     */
    protected $autoClear;

    public function __construct(array $config)
    {
        $this->directory = rtrim($config['directory'], DIRECTORY_SEPARATOR);
        $this->baseUrl = trim($config['base_url'], " \t\n\r\0\x0B\\");

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

        return trim($this->baseUrl, '/').'/'.trim($fileName, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigKey()
    {
        return 'local';
    }
}

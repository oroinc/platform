<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

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
        $this->directory = $config['directory'];
        $this->baseUrl = trim($config['base_url'], " \t\n\r\0\x0B\\");

        if ($config['auto_clear']) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->directory);
        }

        if (!file_exists($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($file)
    {
        $fileName = uniqid().'.png';
        $screenshotName = sprintf(
            '%s/%s',
            $this->directory,
            $fileName
        );

        file_put_contents($screenshotName, $file);

        return $this->baseUrl.'/'.$fileName;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigKey()
    {
        return 'local';
    }
}

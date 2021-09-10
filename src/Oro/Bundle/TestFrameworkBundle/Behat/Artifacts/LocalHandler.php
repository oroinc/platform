<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\TokenGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Saves test artifacts to the local filesystem
 */
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

    private KernelInterface $kernel;

    public function __construct(array $config, KernelInterface $kernel)
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
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function save($file)
    {
        $fileName = TokenGenerator::generateToken('image').'.png';
        $screenshotName = $this->directory.DIRECTORY_SEPARATOR.$fileName;

        file_put_contents($screenshotName, $file);

        if ($this->useLocalUrl()) {
            return $this->getLink('file://'.$screenshotName, $screenshotName);
        }
        $url = trim($this->baseUrl, '/').'/'.trim($fileName, '/');

        return $this->getLink($url, $url);
    }

    private function useLocalUrl(): bool
    {
        $container = $this->kernel->getContainer();
        $installed = (bool)$container->getParameter('installed');
        if (!$installed) {
            return true;
        }
        $applicationUrl = $container->get('oro_config.manager')->get('oro_ui.application_url');
        if (!str_contains($this->baseUrl, $applicationUrl)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigKey()
    {
        return 'local';
    }

    private function getLink(string $url, $title): string
    {
        return "\033]8;;$url\033\\$title\033]8;;\033\\";
    }
}

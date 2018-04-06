<?php

namespace Oro\Bundle\EmailBundle\Cache;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\HttpKernel\KernelInterface;

class EntityCacheWarmer extends CacheWarmer
{
    /**
     * @var EmailOwnerProviderStorage
     */
    protected $emailOwnerProviderStorage;

    /**
     * @var string
     */
    private $entityCacheDir;

    /**
     * @var string
     */
    private $entityCacheNamespace;

    /**
     * @var string
     */
    private $entityProxyNameTemplate;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Constructor.
     *
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param string                    $entityCacheDir
     * @param string                    $entityCacheNamespace
     * @param string                    $entityProxyNameTemplate
     * @param KernelInterface           $kernel
     */
    public function __construct(
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        $entityCacheDir,
        $entityCacheNamespace,
        $entityProxyNameTemplate,
        KernelInterface $kernel
    ) {
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->entityCacheDir            = $entityCacheDir;
        $this->entityCacheNamespace      = $entityCacheNamespace;
        $this->entityProxyNameTemplate   = $entityProxyNameTemplate;
        $this->kernel                    = $kernel;
    }

    /**
     * {inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $fs   = $this->createFilesystem();
        $twig = $this->createTwigEnvironment();

        $this->processEmailAddressTemplate($fs, $twig);
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * Create Filesystem object
     *
     * @return Filesystem
     */
    protected function createFilesystem()
    {
        return new Filesystem();
    }

    /**
     * Create Twig_Environment object
     *
     * @return \Twig_Environment
     */
    protected function createTwigEnvironment()
    {
        $entityTemplateDir = $this->kernel->locateResource('@OroEmailBundle/Resources/cache/Entity');

        return new \Twig_Environment(new \Twig_Loader_Filesystem($entityTemplateDir));
    }

    /**
     * Create a proxy class for EmailAddress entity and save it in cache
     *
     * @param Filesystem        $fs
     * @param \Twig_Environment $twig
     */
    protected function processEmailAddressTemplate(Filesystem $fs, \Twig_Environment $twig)
    {
        // Ensure the cache directory exists
        if (!$fs->exists($this->entityCacheDir)) {
            $fs->mkdir($this->entityCacheDir, 0777);
        }

        $args      = array();
        $providers = $this->emailOwnerProviderStorage->getProviders();
        foreach ($providers as $provider) {
            $args[] = array(
                'targetEntity' => $provider->getEmailOwnerClass(),
                'columnName'   => $this->emailOwnerProviderStorage->getEmailOwnerColumnName($provider),
                'fieldName'    => $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider)
            );
        }

        $className  = sprintf($this->entityProxyNameTemplate, 'EmailAddress');
        $twigParams = array(
            'namespace' => $this->entityCacheNamespace,
            'className' => $className,
            'owners'    => $args
        );

        // generate a proxy class
        $content = $twig->render('EmailAddress.php.twig', $twigParams);
        $this->writeCacheFile(
            sprintf('%s%s%s.php', $this->entityCacheDir, DIRECTORY_SEPARATOR, $className),
            $content
        );
        // generate ORM mappings for a proxy class
        $content = $twig->render('EmailAddress.orm.yml.twig', $twigParams);
        $this->writeCacheFile(
            sprintf('%s%s%s.orm.yml', $this->entityCacheDir, DIRECTORY_SEPARATOR, $className),
            $content
        );
    }
}

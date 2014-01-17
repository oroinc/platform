<?php

namespace Oro\Bundle\EmailBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\Filesystem\Filesystem;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EntityCacheWarmer extends CacheWarmer
{
    /**
     * A list of class names of all email owners
     *
     * @var string[]
     */
    protected $emailOwnerClasses = array();

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
     * Constructor.
     *
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param string                    $entityCacheDir
     * @param string                    $entityCacheNamespace
     * @param string                    $entityProxyNameTemplate
     */
    public function __construct(
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        $entityCacheDir,
        $entityCacheNamespace,
        $entityProxyNameTemplate
    ) {
        foreach ($emailOwnerProviderStorage->getProviders() as $provider) {
            $this->emailOwnerClasses[count($this->emailOwnerClasses) + 1] = $provider->getEmailOwnerClass();
        }

        $this->entityCacheDir          = $entityCacheDir;
        $this->entityCacheNamespace    = $entityCacheNamespace;
        $this->entityProxyNameTemplate = $entityProxyNameTemplate;
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
        $entityTemplateDir = __DIR__ . '/../Resources/cache/Entity';

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

        $args = array();
        foreach ($this->emailOwnerClasses as $key => $emailOwnerClass) {
            $prefix = strtolower(substr($emailOwnerClass, 0, strpos($emailOwnerClass, '\\')));
            if ($prefix === 'oro' || $prefix === 'orocrm') {
                // do not use prefix if email's owner is a part of BAP and CRM
                $prefix = '';
            } else {
                $prefix .= '_';
            }
            $suffix = strtolower(substr($emailOwnerClass, strrpos($emailOwnerClass, '\\') + 1));

            $args[] = array(
                'targetEntity' => $emailOwnerClass,
                'columnName'   => sprintf('owner_%s%s_id', $prefix, $suffix),
                'fieldName'    => sprintf('owner%d', $key)
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

<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Symfony\Component\Finder\Finder;

use Oro\Bundle\TranslationBundle\Provider\AbstractAPIAdapter;

class TranslationUploader
{
    /**
     * @var AbstractAPIAdapter
     */
    protected $adapter;

    /**
     * @param array              $bundles
     * @param AbstractAPIAdapter $adapter
     */
    public function __construct($bundles, AbstractAPIAdapter $adapter = null)
    {
        $this->adapter = $adapter;
    }

    /**
     * Upload translations
     *
     * @param string $dir
     * @param callable $progressCallback
     *
     * @return mixed
     */
    public function upload($dir, \Closure $progressCallback = null)
    {
        $finder = Finder::create()->files()->name('*.yml')->in($dir);

        /** $file \SplFileInfo */
        $files = array();
        foreach ($finder->files() as $file) {
            $files[ str_replace($dir, '', (string)$file) ] = (string)$file;
        }

        if (!is_null($progressCallback)) {
            $this->adapter->setProgressCallback($progressCallback);
        }

        return $this->adapter->upload($files);
    }

    /**
     * @param AbstractAPIAdapter $adapter
     */
    public function setAdapter(AbstractAPIAdapter $adapter)
    {
        $this->adapter = $adapter;
    }
}

<?php

namespace Oro\Bundle\CrowdinBundle\Provider;

use Symfony\Component\Finder\Finder;

class TranslationUploader
{
    /**
     * @var AbstractAPIAdapter
     */
    protected $adapter;

    /**
     * @var array bundles
     */
    protected $bundles;

    /**
     * @param array $bundles
     * @param AbstractAPIAdapter $adapter
     */
    public function __construct($bundles, AbstractAPIAdapter $adapter = null)
    {
        $this->adapter = $adapter;
        $this->bundles = $bundles;
    }

    /**
     * Upload translations
     *
     * @param string $dir
     */
    public function upload($dir)
    {
        // compile file list to be uploaded
        $files = array();

        $finder = Finder::create()->files()->name('*.yml')->in($dir);

        foreach ($finder->files() as $splFileInfo) {

        }

        $results = $this->adapter->upload($files);
    }
}

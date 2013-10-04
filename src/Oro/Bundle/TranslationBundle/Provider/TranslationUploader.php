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
    public function __construct($bundles, AbstractAPIAdapter $adapter)
    {
        $this->adapter = $adapter;
        $this->bundles = $bundles;
    }

    /**
     * Upload translations
     */
    public function upload()
    {
        // compile file list to be uploaded
        $files = array();

        die();
        $finder = Finder::create()->files()->name('*.yml')->in(__DIR__);

        $results = $this->adapter->upload($files);
    }
}

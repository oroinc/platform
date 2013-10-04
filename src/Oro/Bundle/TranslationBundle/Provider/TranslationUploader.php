<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\CrowdinBundle\Provider\AbstractAPIAdapter;
use Symfony\Component\Finder\Finder;

class TranslationUploader
{
    /**
     * @var AbstractAPIAdapter
     */
    protected $adapter;

    /**
     * @param array $bundles
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
     */
    public function upload($dir)
    {
        $finder = Finder::create()->files()->name('*.yml')->in($dir);

        $results = $this->adapter->upload($finder->files());

        var_dump($results);
    }

    /**
     * @param AbstractAPIAdapter $adapter
     */
    public function setAdapter(AbstractAPIAdapter $adapter)
    {
        $this->adapter = $adapter;
    }
}

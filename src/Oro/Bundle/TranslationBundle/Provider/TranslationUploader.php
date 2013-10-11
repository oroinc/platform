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
     * @param AbstractAPIAdapter $adapter
     */
    public function __construct(AbstractAPIAdapter $adapter = null)
    {
        $this->adapter = $adapter;
    }

    /**
     * Upload translations
     *
     * @param string $dir
     * @param string $mode add or update
     * @param callable $progressCallback
     *
     * @return mixed
     */
    public function upload($dir, $mode = 'add', \Closure $progressCallback = null)
    {
        $finder = Finder::create()->files()->name('*.yml')->in($dir);

        /** $file \SplFileInfo */
        $files = array();
        foreach ($finder->files() as $file) {
            // crowdin understand only "/" as directory separator :)
            $apiPath = str_replace(array($dir, DIRECTORY_SEPARATOR), array('', '/'), (string)$file);
            $files[ $apiPath ] = (string)$file;
        }

        if (!is_null($progressCallback)) {
            $this->adapter->setProgressCallback($progressCallback);
        }

        return $this->adapter->upload($files, $mode);
    }

    /**
     * @param AbstractAPIAdapter $adapter
     */
    public function setAdapter(AbstractAPIAdapter $adapter)
    {
        $this->adapter = $adapter;
    }
}

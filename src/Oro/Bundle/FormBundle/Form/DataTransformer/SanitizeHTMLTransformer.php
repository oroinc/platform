<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;

class SanitizeHTMLTransformer implements DataTransformerInterface
{
    const SUB_DIR = 'ezyang';
    const MODE    = 0775;

    /**
     * @var string|null
     */
    protected $allowedElements;

    /**
     * @var string|null
     */
    protected $cacheDir;

    /**
     * @param string|null $allowedElements
     * @param string|null $cacheDir
     */
    public function __construct($allowedElements = null, $cacheDir = null)
    {
        $this->allowedElements = $allowedElements;
        $this->cacheDir        = $cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $this->sanitize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $this->sanitize($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function sanitize($value)
    {
        $config = \HTMLPurifier_Config::createDefault();
        $this->fillAllowedElementsConfig($config);
        $this->fillCacheConfig($config);
        // add inline data support
        $config->set(
            'URI.AllowedSchemes',
            ['http' => true, 'https' => true, 'mailto' => true, 'ftp' => true, 'data' => true, 'tel' => true]
        );
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($value);
    }

    /**
     * Create cache dir if need
     *
     * @param string $cacheDir
     */
    protected function touchCacheDir($cacheDir)
    {
        $fs = new Filesystem();
        if (!$fs->exists($cacheDir)) {
            $fs->mkdir($cacheDir, self::MODE);
        }
    }

    /**
     * Configure cache
     *
     * @param \HTMLPurifier_Config $config
     */
    protected function fillCacheConfig($config)
    {
        if ($this->cacheDir) {
            $cacheDir = $this->cacheDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . self::SUB_DIR;
            $this->touchCacheDir($cacheDir);
            $config->set('Cache.SerializerPath', $cacheDir);
            $config->set('Cache.SerializerPermissions', self::MODE);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }
    }

    /**
     * Configure allowed tags
     *
     * @param \HTMLPurifier_Config $config
     */
    protected function fillAllowedElementsConfig($config)
    {
        $converter = new TagDefinitionConverter();
        if ($this->allowedElements) {
            $config->set('HTML.AllowedElements', $converter->getElements($this->allowedElements));
            $config->set('HTML.AllowedAttributes', $converter->getAttributes($this->allowedElements));
        }
    }
}

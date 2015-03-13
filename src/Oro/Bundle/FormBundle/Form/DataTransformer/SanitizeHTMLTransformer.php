<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;

class SanitizeHTMLTransformer implements DataTransformerInterface
{
    const SUB_DIR = 'ezyang';

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
        $this->cacheDir = $cacheDir;
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
        $converter = new TagDefinitionConverter();
        if ($this->allowedElements) {
            $config->set('HTML.AllowedElements', $converter->getElements($this->allowedElements));
            $config->set('HTML.AllowedAttributes', $converter->getAttributes($this->allowedElements));
        }
        if ($this->cacheDir) {
            $cacheDir = $this->cacheDir . DIRECTORY_SEPARATOR . self::SUB_DIR;
            $this->touchCacheDir($cacheDir);
            $config->set('Cache.SerializerPath', $cacheDir);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($value);
    }

    /**
     * @param string $cacheDir
     */
    protected function touchCacheDir($cacheDir)
    {
        $fs = new Filesystem();
        if (!$fs->exists($cacheDir)) {
            $fs->mkdir($cacheDir, 0777);
        }
    }
}

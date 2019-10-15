<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Sanitizes passed value using html purifier with configured attributes which is enabled
 */
class SanitizeHTMLTransformer implements DataTransformerInterface
{
    const SUB_DIR = 'ezyang';
    const MODE    = 0775;
    const HTMLPURIFIER_CONFIG_REVISION = 2019072301;

    /** @var \HtmlPurifier|null */
    protected $htmlPurifier;

    /** @var string|null */
    protected $allowedElements;

    /** @var string|null */
    protected $cacheDir;

    /** @var HtmlTagProvider */
    private $htmlTagProvider;

    /**
     * @param HtmlTagProvider $htmlTagProvider
     * @param string|null $allowedElements
     * @param string|null $cacheDir
     */
    public function __construct(HtmlTagProvider $htmlTagProvider, $allowedElements = null, $cacheDir = null)
    {
        $this->htmlTagProvider = $htmlTagProvider;
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
        if (!$value) {
            return $value;
        }

        if (!$this->htmlPurifier) {
            $html5Config = \HTMLPurifier_HTML5Config::createDefault();
            $config = \HTMLPurifier_Config::create($html5Config);

            $config->set('HTML.DefinitionID', __CLASS__);
            $config->set('HTML.DefinitionRev', self::HTMLPURIFIER_CONFIG_REVISION);

            // add inline data support
            $config->set('URI.AllowedSchemes', $this->htmlTagProvider->getUriSchemes('default'));
            $config->set('Attr.EnableID', true);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', $this->htmlTagProvider->getIframeRegexp('default'));
            $config->set('Filter.ExtractStyleBlocks.TidyImpl', false);
            $config->set('CSS.AllowImportant', true);
            $config->set('CSS.AllowTricky', true);
            $config->set('CSS.Proprietary', true);
            $config->set('CSS.Trusted', true);

            $this->fillAllowedElementsConfig($config);
            $this->fillCacheConfig($config);

            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addElement(
                    'style',
                    'Block',
                    'Flow',
                    'Common',
                    [
                        'type' => 'Enum#text/css',
                        'media' => 'CDATA',
                    ]
                );
            }

            $this->htmlPurifier = new \HTMLPurifier($config);
        }

        return $this->htmlPurifier->purify($value);
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
        } else {
            $config->set('HTML.Allowed', '');
        }
    }
}

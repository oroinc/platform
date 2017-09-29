<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Symfony\Component\Finder\Finder;

class ContainerTagsDocumentationInformationProvider
{
    /**
     * @var string
     */
    private $installDir;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * @var array
     */
    protected static $excluded = [
        'assetic',
        'doctrine',
        'twig',
        'form',
        'console',
        'security',
        'kernel',
        'validator',
        'routing',
        'translation',
        'serializer',
        'request',
        'templating',
        'property_info',
        'config_cache',
        'monolog',
        'data_collector',
        'snc_',
        'swiftmailer',
        'nelmio',
        'liip_',
        'knp_',
        'jms_',
        'sylius'
    ];

    /**
     * @var array
     */
    protected static $included = ['oro'];

    /**
     * @param string $installDir
     */
    public function __construct($installDir)
    {
        $this->installDir = realpath($installDir);
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return string
     */
    public function getInstallDir()
    {
        return $this->installDir;
    }

    /**
     * @param array $includedTags
     * @param array $excludedTags
     * @return array
     */
    public function getOroTags(array $includedTags, array $excludedTags): array
    {
        $includedTags = array_merge(self::$included, $includedTags);
        $excludedTags = array_merge(self::$excluded, $excludedTags);
        $includedTags = array_diff($includedTags, $excludedTags);

        return array_filter(
            $this->tags,
            function ($tag) use ($includedTags, $excludedTags) {
                foreach ($includedTags as $includedTag) {
                    if (strpos($tag, $includedTag) === 0) {
                        return true;
                    }
                }

                foreach ($excludedTags as $includedTag) {
                    if (strpos($tag, $includedTag) === 0) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * @param array $tags
     * @param bool $skipCodeExamples
     * @return array
     */
    public function getTagsDocumentationInformation(array $tags, $skipCodeExamples): array
    {
        $docsInfo = [];
        foreach ($this->getDocs() as $path => $content) {
            foreach ($tags as $tag) {
                if ($skipCodeExamples) {
                    $content = preg_replace('/```(.*)```/m', '', $content);
                }
                if (!array_key_exists($tag, $docsInfo)) {
                    $docsInfo[$tag] = [];
                }
                if (strpos($content, $tag) !== false) {
                    $docsInfo[$tag][] = $path;
                }
            }
        }

        return $docsInfo;
    }

    /**
     * @return array
     */
    protected function getDocs(): array
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->getOroSourceCodeDir())
            ->followLinks()
            ->name('*.md')
            ->name('*.rst')
            ->notName('UPGRADE*')
            ->notName('CHANGELOG.md');

        $docs = [];
        foreach ($finder as $docFile) {
            $docs[$docFile->getPathname()] = $docFile->getContents();
        }

        return $docs;
    }

    /**
     * @return string
     */
    protected function getOroSourceCodeDir(): string
    {
        return realpath($this->installDir . '/vendor/oro');
    }
}

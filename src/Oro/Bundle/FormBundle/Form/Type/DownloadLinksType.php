<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for displaying downloadable files.
 *
 * This type renders a list of download links for files matching a specified pattern
 * in a given directory. It uses the {@see Finder} component to locate files and the asset
 * helper to generate proper URLs for the download links.
 */
class DownloadLinksType extends AbstractType
{
    /** @var AssetHelper */
    protected $assetHelper;

    public function __construct(AssetHelper $assetHelper)
    {
        $this->assetHelper = $assetHelper;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['source'])
            ->setDefined(['class'])
            ->setDefaults(['class' => ''])
            ->setAllowedTypes('source', 'array');
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['files'] = $this->getFiles($options['source']);
        $view->vars['class'] = $options['class'];
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_download_links_type';
    }

    /**
     * Get files from a specified source data
     *
     * @param array $source
     * @return array
     */
    public function getFiles($source)
    {
        $resources = [];
        if (isset($source['path'], $source['url'])) {
            $finder          = new Finder();
            $pathParts       = explode('/', $source['path']);
            $fileNamePattern = array_pop($pathParts);
            $files = $finder->name($fileNamePattern)->in(implode(DIRECTORY_SEPARATOR, $pathParts));
            /** @var \SplFileInfo[] $files */
            foreach ($files as $file) {
                $resources[$file->getFilename()] = $this->assetHelper->getUrl(
                    rtrim($source['url'], '/') . '/' . $file->getFilename()
                );
            }
        }

        return $resources;
    }
}

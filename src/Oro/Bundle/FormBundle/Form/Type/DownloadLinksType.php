<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadLinksType extends AbstractType
{
    /** @var AssetHelper */
    protected $assetHelper;

    /**
     * @param AssetHelper $assetHelper
     */
    public function __construct(AssetHelper $assetHelper)
    {
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['source'])
            ->setDefined(['class'])
            ->setDefaults(['class' => ''])
            ->setAllowedTypes('source', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['files'] = $this->getFiles($options['source']);
        $view->vars['class'] = $options['class'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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

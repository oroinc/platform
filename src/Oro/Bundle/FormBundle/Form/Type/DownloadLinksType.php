<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DownloadLinksType extends AbstractType
{
    /** @var CoreAssetsHelper */
    protected $assetHelper;

    /**
     * @param CoreAssetsHelper $assetHelper
     */
    public function __construct(CoreAssetsHelper $assetHelper)
    {
        $this->assetHelper   = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['source'])
            ->setOptional(['class'])
            ->setDefaults(['class' => ''])
            ->setAllowedTypes(['source' => 'array']);
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
        $resources       = [];
        if (isset($source['path'], $source['url'])) {
            $finder          = new Finder();
            $pathParts       = explode('/', $source['path']);
            $fileNamePattern = array_pop($pathParts);
            $files = $finder->name($fileNamePattern)->in(implode('/', $pathParts));
            foreach ($files as $file) {
                $resources[$file->getFilename()] = $this->assetHelper->getUrl(
                    $source['url'] . DIRECTORY_SEPARATOR . $file->getFilename()
                );
            }
        }

        return $resources;
    }
}

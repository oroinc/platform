<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractFormProvider
{
    const USED_FOR_CACHE_ONLY_OPTION = 'usedForCacheOnlyOption';

    /** @var array */
    protected $forms = [];

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @param FormFactoryInterface  $formFactory
     * @param UrlGeneratorInterface $router
     */
    public function __construct(FormFactoryInterface $formFactory, UrlGeneratorInterface $router)
    {
        $this->formFactory = $formFactory;
        $this->router = $router;
    }

    /**
     * Build new form
     *
     * @param string $formName
     * @param mixed $data
     * @param array $options
     *
     * @return FormInterface
     */
    protected function getForm($formName, $data = null, array $options = [])
    {
        $cacheKey = $this->getCacheKey($formName, $options);
        unset($options[self::USED_FOR_CACHE_ONLY_OPTION]);

        if (!array_key_exists($cacheKey, $this->forms)) {
            $this->forms[$cacheKey] = $this->formFactory->create($formName, $data, $options);
        }

        return $this->forms[$cacheKey];
    }

    /**
     * Generate Url
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    protected function generateUrl($name, $arguments = array())
    {
        return $this->router->generate($name, $arguments);
    }

    /**
     * Get form cache key
     *
     * @param string $formName
     * @param array $options
     *
     * @return string
     */
    protected function getCacheKey($formName, array $options = [])
    {
        return sprintf('%s:%s', $formName, md5(serialize($options)));
    }
}

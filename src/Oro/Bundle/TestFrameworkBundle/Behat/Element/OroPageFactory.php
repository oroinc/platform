<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Transformers\PageSuffixTransformer;

class OroPageFactory
{
    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array|string[]
     */
    protected $pageAliases = [];

    /**
     * @param OroElementFactory $elementFactory
     * @param array $config
     */
    public function __construct(OroElementFactory $elementFactory, array $config)
    {
        $this->elementFactory = $elementFactory;
        $this->config = $config;
    }

    /**
     * @param $name
     * @return Page
     */
    public function getPage($name)
    {
        $configName = $this->guessName($name);
        if (null === $configName) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find page with "%s" name' .
                PHP_EOL . 'Maybe you forgot to create it?',
                $name
            ));
        }

        $pageConfig = $this->config[$configName];

        return new $pageConfig['class']($this->elementFactory, $pageConfig['route']);
    }

    /**
     * @param string $name
     * @return string|null
     */
    protected function guessName($name)
    {
        if (isset($this->pageAliases[$name])) {
            return $this->pageAliases[$name];
        }
        $variantsIterator = new NameVariantsIterator($name, ['', ' ', '_', '-']);
        $variantsIterator->addPartsTransformer(new PageSuffixTransformer());
        foreach ($variantsIterator as $pageName) {
            if (array_key_exists($pageName, $this->config)) {
                return $this->pageAliases[$name] = $pageName;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasPage($name)
    {
        return null !== $this->guessName($name);
    }
}

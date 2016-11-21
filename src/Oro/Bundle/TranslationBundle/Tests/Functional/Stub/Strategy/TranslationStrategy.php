<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;

class TranslationStrategy implements TranslationStrategyInterface
{
    /** @var string */
    protected $name;

    /** @var array */
    protected $locales;

    /**
     * @param string $name
     * @param array $locales
     */
    public function __construct($name, array $locales)
    {
        $this->name = $name;
        $this->locales = $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocaleFallbacks()
    {
        return $this->locales;
    }
}

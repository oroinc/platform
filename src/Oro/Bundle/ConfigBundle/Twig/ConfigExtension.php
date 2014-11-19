<?php

namespace Oro\Bundle\ConfigBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ConfigExtension extends \Twig_Extension
{
    /** @var ConfigManager */
    protected $cm;

    /**
     * @param ConfigManager $cm
     */
    public function __construct(ConfigManager $cm)
    {
        $this->cm = $cm;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return ['oro_config_value' => new \Twig_Function_Method($this, 'getConfigValue')];
    }

    /**
     * @param  string $name Setting name in "{bundle}.{setting}" format
     *
     * @return mixed
     */
    public function getConfigValue($name)
    {
        return $this->cm->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config_extension';
    }
}

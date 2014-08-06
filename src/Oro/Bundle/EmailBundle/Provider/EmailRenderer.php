<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

class EmailRenderer extends \Twig_Environment
{
    /** @var VariablesProvider */
    protected $variablesProvider;

    /** @var  Cache|null */
    protected $sandBoxConfigCache;

    /** @var  string */
    protected $cacheKey;

    public function __construct(
        \Twig_LoaderInterface $loader,
        $options,
        VariablesProvider $variablesProvider,
        Cache $cache,
        $cacheKey,
        \Twig_Extension_Sandbox $sandbox
    ) {
        parent::__construct($loader, $options);

        $this->variablesProvider  = $variablesProvider;
        $this->sandBoxConfigCache = $cache;
        $this->cacheKey           = $cacheKey;

        $this->addExtension($sandbox);
        $this->configureSandbox();
    }

    /**
     * Configure sandbox form config data
     *
     */
    protected function configureSandbox()
    {
        $allowedData = $this->sandBoxConfigCache->fetch($this->cacheKey);

        if (false === $allowedData) {
            $allowedData = $this->prepareConfiguration();
            $this->sandBoxConfigCache->save($this->cacheKey, serialize($allowedData));
        } else {
            $allowedData = unserialize($allowedData);
        }

        /** @var \Twig_Extension_Sandbox $sandbox */
        $sandbox = $this->getExtension('sandbox');
        /** @var \Twig_Sandbox_SecurityPolicy $security */
        $security = $sandbox->getSecurityPolicy();
        $security->setAllowedProperties($allowedData['properties']);
        $security->setAllowedMethods($allowedData['methods']);
    }

    /**
     * Prepare configuration from entity config
     *
     * @return array
     */
    private function prepareConfiguration()
    {
        $configuration = array();

        $allGetters = $this->variablesProvider->getEntityVariableGetters();
        foreach ($allGetters as $className => $getters) {
            $properties = [];
            $methods    = [];
            foreach ($getters as $varName => $getter) {
                if (empty($getter)) {
                    $properties[] = $varName;
                } else {
                    $methods[] = $getter;
                }
            }

            $configuration['properties'][$className] = $properties;
            $configuration['methods'][$className]    = $methods;
        }

        return $configuration;
    }

    /**
     * Compile email message
     *
     * @param EmailTemplateInterface $template
     * @param array                  $templateParams
     *
     * @return array first element is email subject, second - message
     */
    public function compileMessage(EmailTemplateInterface $template, array $templateParams = array())
    {
        $templateParams['system'] = $this->variablesProvider->getSystemVariableValues();

        $templateRendered = $this->render($template->getContent(), $templateParams);
        $subjectRendered  = $this->render($template->getSubject(), $templateParams);

        return array($subjectRendered, $templateRendered);
    }

    /**
     * Compile preview content
     *
     * @param EmailTemplate $entity
     *
     * @return string
     */
    public function compilePreview(EmailTemplate $entity)
    {
        return $this->render('{% verbatim %}' . $entity->getContent() . '{% endverbatim %}', []);
    }
}

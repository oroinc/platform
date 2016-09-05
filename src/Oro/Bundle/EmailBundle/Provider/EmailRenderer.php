<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Util\Inflector;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

class EmailRenderer extends \Twig_Environment
{
    const VARIABLE_NOT_FOUND = 'oro.email.variable.not.found';

    /**
     * @var array Map of filters to apply to template variables by default
     */
    public static $defaultVariableFilters = ['system.userSignature' => 'oro_html_sanitize'];

    /** @var VariablesProvider */
    protected $variablesProvider;

    /** @var  Cache|null */
    protected $sandBoxConfigCache;

    /** @var  string */
    protected $cacheKey;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PropertyAccessor */
    protected $accessor;

    /** @var array */
    private $systemVariables;

    /**
     * @param \Twig_LoaderInterface   $loader
     * @param array                   $options
     * @param VariablesProvider       $variablesProvider
     * @param Cache                   $cache
     * @param                         $cacheKey
     * @param \Twig_Extension_Sandbox $sandbox
     * @param TranslatorInterface     $translator
     */
    public function __construct(
        \Twig_LoaderInterface $loader,
        $options,
        VariablesProvider $variablesProvider,
        Cache $cache,
        $cacheKey,
        \Twig_Extension_Sandbox $sandbox,
        TranslatorInterface $translator
    ) {
        parent::__construct($loader, $options);

        $this->variablesProvider  = $variablesProvider;
        $this->sandBoxConfigCache = $cache;
        $this->cacheKey           = $cacheKey;

        $this->addExtension($sandbox);
        $this->configureSandbox();

        $this->translator = $translator;
    }

    /**
     * Configure sandbox form config data
     *
     */
    protected function configureSandbox()
    {
        $allowedData = $this->getConfiguration();
        /** @var \Twig_Extension_Sandbox $sandbox */
        $sandbox = $this->getExtension('sandbox');
        /** @var \Twig_Sandbox_SecurityPolicy $security */
        $security = $sandbox->getSecurityPolicy();
        $security->setAllowedProperties($allowedData['properties']);
        $security->setAllowedMethods($allowedData['methods']);
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        $allowedData = $this->sandBoxConfigCache->fetch($this->cacheKey);
        if (false === $allowedData) {
            $allowedData = $this->prepareConfiguration();
            $this->sandBoxConfigCache->save($this->cacheKey, serialize($allowedData));
        } else {
            $allowedData = unserialize($allowedData);
        }

        return $allowedData;
    }

    /**
     * Prepare configuration from entity config
     *
     * @return array
     */
    private function prepareConfiguration()
    {
        $configuration               = [];
        $configuration['formatters'] = [];
        $allGetters                  = $this->variablesProvider->getEntityVariableGetters();
        foreach ($allGetters as $className => $getters) {
            $properties        = [];
            $methods           = [];
            $formatters        = [];
            $defaultFormatters = [];
            foreach ($getters as $varName => $getter) {
                if (empty($getter)) {
                    $properties[] = $varName;
                } else {
                    if (!is_array($getter)) {
                        $methods[] = $getter;
                    } else {
                        $methods[]                   = $getter['property_path'];
                        $formatters[$varName]        = $getter['formatters'];
                        $defaultFormatters[$varName] = $getter['default_formatter'];
                    }
                }
            }

            $configuration['properties'][$className] = $properties;
            $configuration['methods'][$className]    = $methods;

            $configuration['formatters'][$className]        = $formatters;
            $configuration['default_formatter'][$className] = $defaultFormatters;
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
    public function compileMessage(EmailTemplateInterface $template, array $templateParams = [])
    {
        $subject = $template->getSubject();
        $content = $template->getContent();

        $templateRendered = $this->renderWithDefaultFilters($content, $templateParams);
        $subjectRendered  = $this->renderWithDefaultFilters($subject, $templateParams);

        return [$subjectRendered, $templateRendered];
    }

    /**
     * Renders content with default filters
     *
     * @param string $content
     * @param array  $templateParams
     *
     * @return string
     */
    public function renderWithDefaultFilters($content, array $templateParams = [])
    {
        $templateParams['system'] = $this->getSystemVariableValues();
        $content = $this->addDefaultVariableFilters($content);

        if (array_key_exists('entity', $templateParams)) {
            $content = $this->processDefaultFilters($content, $templateParams['entity']);
        }

        return $this->render($content, $templateParams);
    }

    /**
     * Compile preview content
     *
     * @param EmailTemplate $entity
     * @param null|string   $locale
     *
     * @return string
     */
    public function compilePreview(EmailTemplate $entity, $locale = null)
    {
        $content = $entity->getContent();
        if ($locale) {
            foreach ($entity->getTranslations() as $translation) {
                /** @var EmailTemplateTranslation $translation */
                if ($translation->getLocale() === $locale && $translation->getField() === 'content') {
                    $content = $translation->getContent();
                }
            }
        }

        return $this->render('{% verbatim %}' . $content . '{% endverbatim %}', []);
    }


    /**
     * Parses $template and appends filters to the variables defined in self::$defaultVariableFilters map
     *
     * @param string $template
     *
     * @return string
     */
    protected function addDefaultVariableFilters($template)
    {
        foreach (self::$defaultVariableFilters as $var => $filter) {
            $template = preg_replace('/{{\s' . $var . '\s}}/u', sprintf('{{ %s|%s }}', $var, $filter), $template);
        }

        return $template;
    }

    /**
     * Process entity variables what have default filters, for example, datetime form type field
     *
     * Note:
     *  - all tags that do not start with `entity` will be ignored
     *
     * @param string $template
     * @param object $entity
     *
     * @return EmailTemplate
     */
    protected function processDefaultFilters($template, $entity)
    {
        $that     = $this;
        $config   = $that->getConfiguration();
        $callback = function ($match) use ($entity, $that, $config) {
            $result = $match[0];
            $path   = $match[1];
            $split  = explode('.', $path);

            if ($split[0] && 'entity' === $split[0]) {
                unset($split[0]);
                try {
                    $propertyPath = array_pop($split);
                    $value        = $entity;
                    if (count($split)) {
                        $value = $that->getValue($entity, implode('.', $split));
                    }

                    // check if value exists
                    $that->getValue($value, $propertyPath);

                    $propertyName = lcfirst(Inflector::classify($propertyPath));
                    if (is_object($value) && array_key_exists('default_formatter', $config)) {
                        $valueClass       = ClassUtils::getRealClass($value);
                        $defaultFormatter = $config['default_formatter'];
                        if (array_key_exists($valueClass, $defaultFormatter)
                            && array_key_exists($propertyName, $defaultFormatter[$valueClass])
                            && !is_null($defaultFormatter[$valueClass][$propertyName])
                        ) {
                            return sprintf(
                                '{{ %s|oro_format(\'%s\') }}',
                                $path,
                                $config['default_formatter'][ClassUtils::getRealClass($value)][$propertyName]
                            );
                        }
                    }

                    return sprintf('{{ %s|oro_html_sanitize }}', $path);
                } catch (\Exception $e) {
                    $result = $that->translator->trans(self::VARIABLE_NOT_FOUND);
                }
            }

            return $result;
        };

        return preg_replace_callback('/{{\s([\w\d\.\_\-]*?)\s}}/u', $callback, $template);
    }

    /**
     * @param Object $entity
     * @param string $path
     *
     * @return mixed
     */
    protected function getValue($entity, $path)
    {
        $propertyAccess = $this->getPropertyAccess();

        return $propertyAccess->getValue($entity, $path);
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccess()
    {
        if (!$this->accessor instanceof PropertyAccessor) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }

    /**
     * @return array
     */
    protected function getSystemVariableValues()
    {
        if (null === $this->systemVariables) {
            $this->systemVariables = $this->variablesProvider->getSystemVariableValues();
        }

        return $this->systemVariables;
    }
}

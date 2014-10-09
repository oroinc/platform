<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;


class EmailRenderer extends \Twig_Environment
{
    const VARIABLE_NOT_FOUND = 'oro.email.variable.not.found';

    /** @var VariablesProvider */
    protected $variablesProvider;

    /** @var  Cache|null */
    protected $sandBoxConfigCache;

    /** @var  string */
    protected $cacheKey;

    /** @var TranslatorInterface */
    protected $translator;

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

        if (isset($templateParams['entity'])) {
            $template = $this->processDateTimeVariables($template, $templateParams['entity']);
        }

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

    /**
     * Process entity variables of dateTime type
     *   -- datetime variables with formatting will be skipped, e.g. {{ entity.createdAt|date('F j, Y, g:i A') }}
     *   -- processes ONLY variables that passed without formatting, e.g. {{ entity.createdAt }}
     *
     * Note:
     *  - add oro_format_datetime filter to all items which implement \DateTimeInterface
     *  - if value does not exists and PropertyAccess::getValue throw an error
     *    it will change on self::VARIABLE_NOT_FOUND
     *
     * @param EmailTemplateInterface $emailTemplate
     * @param                        $entity
     *
     * @return EmailTemplate
     */
    protected function processDateTimeVariables(EmailTemplateInterface $emailTemplate, $entity)
    {
        $emailTemplateContent = $emailTemplate->getContent();
        $emailTemplateSubject = $emailTemplate->getSubject();

        $contentMatch         = $this->getTagsFromSubject('/{{[\s]*?([\w\d\.\_\-]*?)[\s]*?}}/', $emailTemplateContent);
        $emailTemplateContent = $this->modifyTags(
            $emailTemplateContent,
            $contentMatch,
            $entity,
            function ($path) {
                return '{{ ' . $path . '|oro_format_datetime }}';
            }
        );

        $subjectMatch         = $this->getTagsFromSubject('/{{[\s]*?([\w\d\.\_\-]*?)[\s]*?}}/', $emailTemplateSubject);
        $emailTemplateSubject = $this->modifyTags(
            $emailTemplateSubject,
            $subjectMatch,
            $entity,
            function ($path) {
                return '{{ ' . $path . '|oro_format_datetime }}';
            }
        );

        $emailTemplate->setContent($emailTemplateContent);
        $emailTemplate->setSubject($emailTemplateSubject);

        return $emailTemplate;
    }

    /**
     * @param Object $entity
     * @param string $path
     *
     * @return mixed
     */
    protected function getValue($entity, $path)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        return $accessor->getValue($entity, $path);
    }

    /**
     * @param string $pattern
     * @param string $subject
     *
     * @return array
     */
    protected function getTagsFromSubject($pattern, $subject)
    {
        $match = [];
        preg_match_all($pattern, $subject, $match);

        return empty($match[1]) ? [] : $match[1];
    }

    /**
     * @param string   $subject
     * @param array    $match
     * @param Object   $entity
     * @param callable $replacePattern
     *
     * @return string
     */
    protected function modifyTags($subject, array $match, $entity, \Closure $replacePattern)
    {
        foreach ($match as $path) {
            $split         = explode('.', $path);
            $searchPattern = '/{{[\s]*?' . $path . '[\s]*?}}/';

            if ($split[0] && 'entity' === $split[0]) {
                unset($split[0]);
            }

            try {
                $result = $this->getValue($entity, implode('.', $split));

                if ($result instanceof \DateTimeInterface) {
                    $subject = preg_replace($searchPattern, $replacePattern($path), $subject);
                }
            } catch (\Exception $e) {
                $subject = preg_replace(
                    $searchPattern,
                    '<' . $this->translator->trans(self::VARIABLE_NOT_FOUND) . '>',
                    $subject
                );
            }
        }

        return $subject;
    }
}

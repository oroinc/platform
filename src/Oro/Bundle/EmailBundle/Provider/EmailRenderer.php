<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class EmailRenderer extends \Twig_Environment
{
    /** @var VariablesProvider */
    protected $variablesProvider;

    /** @var  Cache|null */
    protected $sandBoxConfigCache;

    /** @var  string */
    protected $cacheKey;

    /** @var  DateTimeFormatter */
    protected $dateTimeFormatter;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param \Twig_LoaderInterface   $loader
     * @param array                   $options
     * @param VariablesProvider       $variablesProvider
     * @param Cache                   $cache
     * @param                         $cacheKey
     * @param \Twig_Extension_Sandbox $sandbox
     * @param DateTimeFormatter       $dateTimeFormatter
     * @param ManagerRegistry         $doctrine
     */
    public function __construct(
        \Twig_LoaderInterface $loader,
        $options,
        VariablesProvider $variablesProvider,
        Cache $cache,
        $cacheKey,
        \Twig_Extension_Sandbox $sandbox,
        DateTimeFormatter $dateTimeFormatter,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($loader, $options);

        $this->variablesProvider  = $variablesProvider;
        $this->sandBoxConfigCache = $cache;
        $this->cacheKey           = $cacheKey;

        $this->addExtension($sandbox);
        $this->configureSandbox();

        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->doctrine          = $doctrine;
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
     * Note: Rendering \DateTime objects (without formatting) in twig
     *       causes Calling "__tostring" method on a "DateTime" object is not allowed
     *
     * @param EmailTemplateInterface $emailTemplate
     * @param                        $entity
     *
     * @return EmailTemplate
     */
    protected function processDateTimeVariables(EmailTemplateInterface $emailTemplate, $entity)
    {
        $entityManager  = $this->doctrine->getManager();
        $entityMetadata = $entityManager->getClassMetadata(ClassUtils::getClass($entity));
        if ($entityMetadata) {
            $accessor = PropertyAccess::createPropertyAccessor();

            $emailTemplateContent = $emailTemplate->getContent();
            $emailTemplateSubject = $emailTemplate->getSubject();

            $entityFieldMappings = $entityMetadata->fieldMappings;
            array_walk(
                $entityFieldMappings,
                function ($field) use ($entity, $accessor, &$emailTemplateContent, &$emailTemplateSubject) {
                    if (in_array($field['type'], [Type::DATE, Type::TIME, Type::DATETIME, Type::DATETIMETZ])) {
                        $value   = $accessor->getValue($entity, $field['fieldName']);
                        $pattern = '/{{(\s|)entity.' . $field['fieldName'] . '(\s|)}}/';

                        $emailTemplateContent = preg_replace(
                            $pattern,
                            $this->dateTimeFormatter->format($value),
                            $emailTemplateContent
                        );
                        $emailTemplateSubject = preg_replace(
                            $pattern,
                            $this->dateTimeFormatter->format($value),
                            $emailTemplateSubject
                        );
                    }
                }
            );

            $emailTemplate->setContent($emailTemplateContent);
            $emailTemplate->setSubject($emailTemplateSubject);
        }

        return $emailTemplate;
    }
}

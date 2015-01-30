<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntax;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class EmailTemplateSyntaxValidator extends ConstraintValidator
{
    /** @var \Twig_Environment */
    protected $twig;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param \Twig_Environment   $twig
     * @param LocaleSettings      $localeSettings
     * @param ConfigProvider      $entityConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        \Twig_Environment $twig,
        LocaleSettings $localeSettings,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        $this->twig                 = $twig;
        $this->localeSettings       = $localeSettings;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator           = $translator;
    }

    /**
     * @param EmailTemplate                  $value
     * @param Constraint|EmailTemplateSyntax $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        // prepare templates to be validated
        $itemsToValidate = [
            ['field' => 'subject', 'locale' => null, 'template' => $value->getSubject()],
            ['field' => 'content', 'locale' => null, 'template' => $value->getContent()],
        ];
        $translations    = $value->getTranslations();
        foreach ($translations as $trans) {
            if (in_array($trans->getField(), ['subject', 'content'])) {
                $itemsToValidate[] = [
                    'field'    => $trans->getField(),
                    'locale'   => $trans->getLocale(),
                    'template' => $trans->getContent()
                ];
            }
        }

        /** @var \Twig_Extension_Sandbox $sandbox */
        $sandbox = $this->twig->getExtension('sandbox');
        $sandbox->enableSandbox();

        // validate templates' syntax
        $errors = [];
        foreach ($itemsToValidate as &$item) {
            try {
                $this->twig->parse($this->twig->tokenize($item['template']));
            } catch (\Twig_Error_Syntax $e) {
                $errors[] = [
                    'field'  => $item['field'],
                    'locale' => $item['locale'],
                    'error'  => $e->getMessage()
                ];
            }
        }

        $sandbox->disableSandbox();

        // add violations for found errors
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->addViolation(
                    $constraint->message,
                    [
                        '{{ field }}'  => $this->getFieldLabel(ClassUtils::getClass($value), $error['field']),
                        '{{ locale }}' => $this->getLocaleName($error['locale']),
                        '{{ error }}'  => $error['error']
                    ]
                );
            }
        }
    }

    /**
     * Gets translated field name by its name
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     */
    protected function getFieldLabel($className, $fieldName)
    {
        if (!$this->entityConfigProvider->hasConfig($className, $fieldName)) {
            return $fieldName;
        }

        $fieldLabel = $this->entityConfigProvider->getConfig($className, $fieldName)->get('label');

        return $this->translator->trans($fieldLabel);
    }

    /**
     * Gets translated locale name by its code
     *
     * @param string|null $locale The locale code. NULL means default locale
     *
     * @return string
     */
    protected function getLocaleName($locale)
    {
        $currentLang = $this->localeSettings->getLanguage();
        if (empty($locale)) {
            $locale = $currentLang;
        }

        $localeNames = $this->localeSettings->getLocalesByCodes([$locale], $currentLang);

        return $localeNames[$locale];
    }
}

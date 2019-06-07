<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntax;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Twig\Error\SyntaxError;

/**
 * Validates email template syntax.
 */
class EmailTemplateSyntaxValidator extends ConstraintValidator
{
    /** @var EmailRenderer */
    private $emailRenderer;

    /** @var LocaleSettings */
    private $localeSettings;

    /** @var ConfigProvider */
    private $entityConfigProvider;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param EmailRenderer       $emailRenderer
     * @param LocaleSettings      $localeSettings
     * @param ConfigProvider      $entityConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EmailRenderer $emailRenderer,
        LocaleSettings $localeSettings,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        $this->emailRenderer = $emailRenderer;
        $this->localeSettings = $localeSettings;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
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
        $translations = $value->getTranslations();
        foreach ($translations as $trans) {
            if (in_array($trans->getField(), ['subject', 'content'], true)) {
                $itemsToValidate[] = [
                    'field'    => $trans->getField(),
                    'locale'   => $trans->getLocale(),
                    'template' => $trans->getContent()
                ];
            }
        }

        $errors = [];
        foreach ($itemsToValidate as $item) {
            if (!$item['template']) {
                continue;
            }

            try {
                $this->emailRenderer->validateTemplate($item['template']);
            } catch (SyntaxError $e) {
                $errors[] = [
                    'field'  => $item['field'],
                    'locale' => $item['locale'],
                    'error'  => $e->getMessage()
                ];
            }
        }

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
    private function getFieldLabel($className, $fieldName)
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
    private function getLocaleName($locale)
    {
        $currentLang = $this->localeSettings->getLanguage();
        if (!$locale) {
            $locale = $currentLang;
        }

        $localeNames = $this->localeSettings->getLocalesByCodes([$locale], $currentLang);

        return $localeNames[$locale];
    }
}

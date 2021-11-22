<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\SyntaxError;

/**
 * Validates that an email template does not have syntax errors.
 */
class EmailTemplateSyntaxValidator extends ConstraintValidator
{
    private EmailRenderer $emailRenderer;
    private LocalizationManager $localizationManager;
    private ConfigProvider $entityConfigProvider;
    private TranslatorInterface $translator;

    public function __construct(
        EmailRenderer $emailRenderer,
        LocalizationManager $localizationManager,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        $this->emailRenderer = $emailRenderer;
        $this->localizationManager = $localizationManager;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailTemplateSyntax) {
            throw new UnexpectedTypeException($constraint, EmailTemplateSyntax::class);
        }
        if (!$value instanceof EmailTemplate) {
            throw new UnexpectedTypeException($value, EmailTemplate::class);
        }

        // prepare templates to be validated
        $itemsToValidate = [
            ['field' => 'subject', 'locale' => null, 'template' => $value->getSubject()],
            ['field' => 'content', 'locale' => null, 'template' => $value->getContent()],
        ];

        foreach ($value->getTranslations() as $templateTranslation) {
            if (!$templateTranslation->isSubjectFallback()) {
                $itemsToValidate[] = [
                    'field' => 'subject',
                    'locale' => $templateTranslation->getLocalization(),
                    'template' => $templateTranslation->getSubject(),
                ];
            }

            if (!$templateTranslation->isContentFallback()) {
                $itemsToValidate[] = [
                    'field' => 'content',
                    'locale' => $templateTranslation->getLocalization(),
                    'template' => $templateTranslation->getContent(),
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
                    'field' => $item['field'],
                    'locale' => $item['locale'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // add violations for found errors
        if (!empty($errors)) {
            $defaultLocalization = $this->localizationManager->getDefaultLocalization();
            foreach ($errors as $error) {
                $localization = $error['locale'] ?? $defaultLocalization;
                $this->context->addViolation(
                    $constraint->message,
                    [
                        '{{ field }}' => $this->getFieldLabel(ClassUtils::getClass($value), $error['field']),
                        '{{ locale }}' => (string)$localization->getTitle($defaultLocalization),
                        '{{ error }}' => $error['error'],
                    ]
                );
            }
        }
    }

    /**
     * Gets translated field name by its name.
     */
    private function getFieldLabel(string $className, string $fieldName): string
    {
        if (!$this->entityConfigProvider->hasConfig($className, $fieldName)) {
            return $fieldName;
        }

        $fieldLabel = (string)$this->entityConfigProvider->getConfig($className, $fieldName)->get('label');

        return $this->translator->trans($fieldLabel);
    }
}

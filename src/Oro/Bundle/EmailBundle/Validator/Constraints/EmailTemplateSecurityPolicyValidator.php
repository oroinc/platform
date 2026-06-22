<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\EmailTemplateSecurityPolicyCheckerInterface;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFilterViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFunctionViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyMethodViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyPropertyViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyTagViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyViolationInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\SyntaxError;

/**
 * Validates that all fields of an email template and its localizations comply with the Twig sandbox security policy.
 *
 * Accepts {@see EmailTemplateInterface} instances.
 * When an entity is provided, iterates over the default template and every non-fallback localized translation.
 * When a plain model is provided, only the default template content is validated (no translation variants).
 *
 * Collects security policy violations from {@see EmailTemplateSecurityPolicyCheckerInterface},
 * and reports each one as a Symfony constraint violation with a message tailored to the violation kind.
 */
class EmailTemplateSecurityPolicyValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EmailTemplateSecurityPolicyCheckerInterface $securityPolicyChecker,
        private readonly TranslatedEmailTemplateProvider $translatedEmailTemplateProvider,
        private readonly LocalizationManager $localizationManager,
        private readonly ConfigProvider $entityConfigProvider,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailTemplateSecurityPolicy) {
            throw new UnexpectedTypeException($constraint, EmailTemplateSecurityPolicy::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof EmailTemplateInterface) {
            throw new UnexpectedTypeException($value, EmailTemplateInterface::class);
        }

        $entityClass = ClassUtils::getClass($value);
        $defaultLocalization = $this->localizationManager->getDefaultLocalization();

        $this->validateDefaultTemplate($value, $constraint, $entityClass, $defaultLocalization);

        if ($value instanceof EmailTemplate) {
            $this->validateTranslations($value, $constraint, $entityClass, $defaultLocalization);
        }
    }

    /**
     * Validates the default (non-translated) email template against the security policy.
     */
    private function validateDefaultTemplate(
        EmailTemplateInterface $emailTemplate,
        EmailTemplateSecurityPolicy $constraint,
        string $entityClass,
        ?Localization $defaultLocalization
    ): void {
        try {
            foreach ($this->securityPolicyChecker->checkSecurityPolicy($emailTemplate) as $violation) {
                $this->addSecurityPolicyViolation(
                    $constraint,
                    $violation,
                    $entityClass,
                    $defaultLocalization,
                    $defaultLocalization
                );
            }
        } catch (SyntaxError) {
            // Twig syntax errors are out of the responsibility scope.
        }
    }

    /**
     * Validates all localized translations of an email template against the security policy.
     */
    private function validateTranslations(
        EmailTemplate $emailTemplate,
        EmailTemplateSecurityPolicy $constraint,
        string $entityClass,
        ?Localization $defaultLocalization
    ): void {
        foreach ($emailTemplate->getTranslations() as $emailTemplateTranslation) {
            if ($this->isTranslationFallback($emailTemplateTranslation)) {
                continue;
            }

            $this->validateTranslationTemplate(
                $emailTemplate,
                $emailTemplateTranslation,
                $constraint,
                $entityClass,
                $defaultLocalization
            );
        }
    }

    /**
     * Checks if a translation should be skipped as it only contains fallback values.
     */
    private function isTranslationFallback(object $emailTemplateTranslation): bool
    {
        return $emailTemplateTranslation->isSubjectFallback() && $emailTemplateTranslation->isContentFallback();
    }

    /**
     * Validates a single translation template against the security policy.
     */
    private function validateTranslationTemplate(
        EmailTemplate $emailTemplate,
        object $emailTemplateTranslation,
        EmailTemplateSecurityPolicy $constraint,
        string $entityClass,
        ?Localization $defaultLocalization
    ): void {
        $localization = $emailTemplateTranslation->getLocalization();
        $translatedTemplate = $this->translatedEmailTemplateProvider
            ->getTranslatedEmailTemplate($emailTemplate, $localization);

        try {
            foreach ($this->securityPolicyChecker->checkSecurityPolicy($translatedTemplate) as $violation) {
                $this->addSecurityPolicyViolation(
                    $constraint,
                    $violation,
                    $entityClass,
                    $localization,
                    $defaultLocalization
                );
            }
        } catch (SyntaxError) {
            // Twig syntax errors are out of the responsibility scope.
        }
    }

    private function addSecurityPolicyViolation(
        EmailTemplateSecurityPolicy $constraint,
        EmailTemplateSecurityPolicyViolationInterface $violation,
        string $entityClass,
        ?Localization $localization,
        ?Localization $defaultLocalization
    ): void {
        switch (true) {
            case $violation instanceof EmailTemplateSecurityPolicyTagViolation:
                $message = $constraint->tagMessage;
                $errorCode = $constraint::NOT_ALLOWED_TAG_ERROR;
                break;
            case $violation instanceof EmailTemplateSecurityPolicyFilterViolation:
                $message = $constraint->filterMessage;
                $errorCode = $constraint::NOT_ALLOWED_FILTER_ERROR;
                break;
            case $violation instanceof EmailTemplateSecurityPolicyFunctionViolation:
                $message = $constraint->functionMessage;
                $errorCode = $constraint::NOT_ALLOWED_FUNCTION_ERROR;
                break;
            case $violation instanceof EmailTemplateSecurityPolicyPropertyViolation:
                $message = $constraint->propertyMessage;
                $errorCode = $constraint::NOT_ALLOWED_PROPERTY_ERROR;
                break;
            case $violation instanceof EmailTemplateSecurityPolicyMethodViolation:
                $message = $constraint->methodMessage;
                $errorCode = $constraint::NOT_ALLOWED_METHOD_ERROR;
                break;
            default:
                throw new \UnexpectedValueException(
                    sprintf('Unexpected security policy violation type "%s".', get_class($violation))
                );
        }

        $this->context
            ->buildViolation($message, [
                '{{ field }}' => $this->getFieldLabel($entityClass, $violation->getTemplateField()),
                '{{ locale }}' => (string)$localization?->getTitle($defaultLocalization),
                '{{ name }}' => $violation->getName(),
                '{{ variable }}' => $violation->getVariableName(),
            ])
            ->setCode($errorCode)
            ->setCause($violation)
            ->addViolation();
    }

    /**
     * Returns the translated label for a field on the given entity class,
     * falling back to the raw field name when no entity config exists.
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

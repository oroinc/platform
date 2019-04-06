<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorRegistry;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Renders email template as TWIG template in a sandboxed environment.
 */
class EmailRenderer extends TemplateRenderer
{
    private const VARIABLE_NOT_FOUND = 'oro.email.variable.not.found';

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param \Twig_Environment                       $environment
     * @param TemplateRendererConfigProviderInterface $configProvider
     * @param VariableProcessorRegistry               $variableProcessors
     * @param TranslatorInterface                     $translator
     */
    public function __construct(
        \Twig_Environment $environment,
        TemplateRendererConfigProviderInterface $configProvider,
        VariableProcessorRegistry $variableProcessors,
        TranslatorInterface $translator
    ) {
        parent::__construct($environment, $configProvider, $variableProcessors);
        $this->translator = $translator;
    }

    /**
     * Compiles the given email template.
     *
     * @param EmailTemplateInterface $template
     * @param array                  $templateParams
     *
     * @return array [email subject, email body]
     *
     * @throws \Twig_Error if the given template cannot be compiled
     */
    public function compileMessage(EmailTemplateInterface $template, array $templateParams = []): array
    {
        $subjectTemplate = $template->getSubject();
        $contentTemplate = $template->getContent();

        $subject = $subjectTemplate ? $this->renderTemplate($subjectTemplate, $templateParams) : '';
        $content = $contentTemplate ? $this->renderTemplate($contentTemplate, $templateParams) : '';

        return [$subject, $content];
    }

    /**
     * Compiles the given email template for the preview purposes.
     *
     * @param EmailTemplate $template
     * @param string|null   $locale
     *
     * @return string
     *
     * @throws \Twig_Error if the given template cannot be compiled
     */
    public function compilePreview(EmailTemplate $template, string $locale = null): string
    {
        $this->ensureSandboxConfigured();

        $content = $template->getContent();
        if ($locale) {
            foreach ($template->getTranslations() as $translation) {
                /** @var EmailTemplateTranslation $translation */
                if ($translation->getLocale() === $locale && $translation->getField() === 'content') {
                    $content = $translation->getContent();
                }
            }
        }

        return $this->environment->render('{% verbatim %}' . $content . '{% endverbatim %}');
    }

    /**
     * {@inheritdoc}
     */
    protected function getVariableNotFoundMessage(): string
    {
        return $this->translator->trans(self::VARIABLE_NOT_FOUND);
    }
}

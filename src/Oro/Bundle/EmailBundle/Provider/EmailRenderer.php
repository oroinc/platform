<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Renders email template as TWIG template in a sandboxed environment.
 */
class EmailRenderer extends TemplateRenderer
{
    private const VARIABLE_NOT_FOUND = 'oro.email.variable.not.found';

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        Environment $environment,
        TemplateRendererConfigProviderInterface $configProvider,
        VariableProcessorRegistry $variableProcessors,
        TranslatorInterface $translator,
        Inflector $inflector
    ) {
        parent::__construct($environment, $configProvider, $variableProcessors, $inflector);
        $this->translator = $translator;
    }

    /**
     * Compiles the given email template.
     *
     * @param EmailTemplateInterface $template
     * @param array $templateParams
     *
     * @return array [email subject, email body]
     *
     * @throws \Twig\Error\Error if the given template cannot be compiled
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
     * @throws \Twig\Error\Error if the given template cannot be compiled
     */
    public function compilePreview(EmailTemplateInterface $template): string
    {
        $this->ensureSandboxConfigured();

        return $this->environment
            ->createTemplate('{% verbatim %}' . $template->getContent() . '{% endverbatim %}')
            ->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function getVariableNotFoundMessage(): string
    {
        return $this->translator->trans(self::VARIABLE_NOT_FOUND);
    }
}

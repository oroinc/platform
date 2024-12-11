<?php

namespace Oro\Bundle\FormBundle\Twig;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;
use Oro\Bundle\FormBundle\Form\Twig\DataBlockRenderer;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Node\RenderBlockNode;
use Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for form rendering:
 *   - form_data_blocks
 *   - oro_form_js_validation
 *   - form_javascript
 *   - form_stylesheet
 *   - form_row_collection
 */
class FormExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const DEFAULT_TEMPLATE = '@OroForm/Form/fields.html.twig';
    private const BLOCK_NAME = 'oro_form_js_validation';

    private ContainerInterface $container;
    private string $templateName;
    private array $defaultOptions;

    public function __construct(
        ContainerInterface $container,
        string $templateName = self::DEFAULT_TEMPLATE,
        array $defaultOptions = []
    ) {
        $this->container = $container;
        $this->templateName = $templateName;
        $this->defaultOptions = $defaultOptions;
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'form_data_blocks',
                [$this, 'renderFormDataBlocks'],
                ['needs_context' => true, 'needs_environment' => true]
            ),
            new TwigFunction(
                'oro_form_js_validation',
                [$this, 'renderFormJsValidationBlock'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'form_javascript',
                [$this, 'renderJavascript'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'form_stylesheet',
                null,
                ['is_safe' => ['html'], 'node_class' => SearchAndRenderBlockNode::class]
            ),
            new TwigFunction(
                'form_row_collection',
                null,
                ['is_safe' => ['html'], 'node_class' => RenderBlockNode::class]
            ),
            new TwigFunction(
                'is_form_protected_with_captcha',
                [$this, 'isFormProtectedWithCaptcha']
            ),
            new TwigFunction(
                'get_captcha_form_element',
                [$this, 'getCaptchaFormElement']
            )
        ];
    }

    /**
     * @param Environment       $env
     * @param array             $context
     * @param FormView          $form
     * @param string            $formVariableName
     *
     * @return array
     */
    public function renderFormDataBlocks(Environment $env, $context, FormView $form, $formVariableName = 'form')
    {
        return $this->getDataBlockRenderer()->render($env, $context, $form, $formVariableName);
    }

    /**
     * Renders "oro_form_js_validation" block with init script for JS validation of form.
     *
     * @param Environment $environment
     * @param FormView $view
     * @param array $options
     *
     * @return string
     * @throws \Throwable
     */
    public function renderFormJsValidationBlock(Environment $environment, FormView $view, $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        $template = $environment->load($this->templateName);
        if (!$template->hasBlock(self::BLOCK_NAME, [])) {
            throw new \RuntimeException(
                sprintf('Block "%s" is not found in template "%s".', self::BLOCK_NAME, $this->templateName)
            );
        }

        return $template->renderBlock(
            self::BLOCK_NAME,
            [
                'form'       => $view,
                'options'    => $options,
                'js_options' => $this->filterJsOptions($options)
            ]
        );
    }

    /**
     * Render Function Form Javascript
     *
     * @param FormView $view
     * @param bool $prototype
     *
     * @return string
     */
    public function renderJavascript(FormView $view, $prototype = false)
    {
        $block = $prototype ? 'javascript_prototype' : 'javascript';

        return $this->getFormRenderer()->searchAndRenderBlock($view, $block);
    }

    /**
     * Exclude object values.
     *
     * @param array $options
     *
     * @return array
     */
    private function filterJsOptions(array $options)
    {
        foreach ($options as $name => $value) {
            if (is_object($value)) {
                unset($options[$name]);
            }
            if (is_array($value)) {
                $options[$name] = $this->filterJsOptions($value);
            }
        }

        return $options;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'form.factory' => FormFactoryInterface::class,
            'oro_form.captcha.settings_provider' => CaptchaSettingsProviderInterface::class,
            'twig.form.renderer' => FormRendererInterface::class,
        ];
    }

    public function isFormProtectedWithCaptcha(string $formName): bool
    {
        /** @var CaptchaSettingsProviderInterface $settingsProvider */
        $settingsProvider = $this->container->get('oro_form.captcha.settings_provider');

        return $settingsProvider->isProtectionAvailable()
            && $settingsProvider->isFormProtected($formName);
    }

    public function getCaptchaFormElement(string $name = 'captcha'): FormView
    {
        /** @var CaptchaSettingsProviderInterface $settingsProvider */
        $settingsProvider = $this->container->get('oro_form.captcha.settings_provider');
        /** @var FormFactoryInterface $formFactory */
        $formFactory = $this->container->get('form.factory');

        $captchaField = $formFactory->createNamed($name, $settingsProvider->getFormType());

        return $captchaField->createView();
    }

    private function getFormRenderer(): FormRendererInterface
    {
        return $this->container->get('twig.form.renderer');
    }

    private function getDataBlockRenderer(): DataBlockRenderer
    {
        return new DataBlockRenderer();
    }
}

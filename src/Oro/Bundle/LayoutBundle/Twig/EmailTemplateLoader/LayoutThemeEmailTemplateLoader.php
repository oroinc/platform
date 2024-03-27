<?php

namespace Oro\Bundle\LayoutBundle\Twig\EmailTemplateLoader;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\EmailTemplateLoaderInterface;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\EmailTemplateLoaderParsingTrait;
use Twig\Loader\FilesystemLoader;
use Twig\Source;

/**
 * Loads an email template from filesystem taking into account the layout theme taken from parsed parameters.
 *
 * Examples of the supported email template names:
 * @theme:name=default/sample_template_name
 */
class LayoutThemeEmailTemplateLoader extends FilesystemLoader implements EmailTemplateLoaderInterface
{
    use EmailTemplateLoaderParsingTrait;

    public function exists($name): bool
    {
        $templateName = $this->normalizeName($name);
        if (!$templateName) {
            return false;
        }

        return parent::exists($templateName);
    }

    private function normalizeName(string $name): string
    {
        [$emailTemplateCriteria, $templateContext] = $this->parseName($name, 'theme');
        if ($emailTemplateCriteria === null || empty($templateContext['name'])) {
            return '';
        }

        $templateName = '@' . $templateContext['name'] . '/' . $emailTemplateCriteria->getName();

        return preg_replace('#/{2,}#', '/', str_replace('\\', '/', $templateName));
    }

    public function getCacheKey($name): string
    {
        if (!$this->exists($name)) {
            return '';
        }

        $templateName = $this->normalizeName($name);

        return parent::getCacheKey($templateName);
    }

    public function isFresh($name, $time): bool
    {
        $templateName = $this->normalizeName($name);

        return parent::isFresh($templateName, $time);
    }

    public function getSourceContext($name): Source
    {
        $templateName = $this->normalizeName($name);

        if (null === $path = $this->findTemplate($templateName)) {
            return new Source('', $name, '');
        }

        return new Source(file_get_contents($path), $name, $path);
    }

    public function getEmailTemplate(string $name): EmailTemplateModel
    {
        return EmailTemplateModel::createFromContent(
            $this->getSourceContext($name)->getCode()
        );
    }
}

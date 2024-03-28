<?php

namespace Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

/**
 * Contains handy method for parsing an email template name.
 */
trait EmailTemplateLoaderParsingTrait
{
    /**
     * @param string $name Email template name.
     *  Example: '@db:entityName=Acme\Bundle\Entity\SampleEntity/sample_template_name'
     * @param string $namespace Expected email template namespace
     *  Example: 'db'
     *
     * @return array{EmailTemplateCriteria,array<string,string>}
     *  Example: [
     *      EmailTemplateCriteria $emailTemplateCriteria,
     *      ['localization' => 42]
     *  ]
     */
    private function parseName(string $name, string $namespace): array
    {
        if (!str_starts_with($name, '@' . $namespace . ':')) {
            return [null, []];
        }

        $templateName = substr($name, strpos($name, '/') + 1);
        parse_str(substr($name, strlen($namespace) + 2, -1 * strlen($templateName) - 1), $templateContext);

        $emailTemplateCriteria = new EmailTemplateCriteria($templateName, $templateContext['entityName'] ?? null);
        unset($templateContext['entityName']);

        return [$emailTemplateCriteria, $templateContext];
    }

    private function isNamespaced(string $name): bool
    {
        return str_starts_with($name, '@');
    }
}

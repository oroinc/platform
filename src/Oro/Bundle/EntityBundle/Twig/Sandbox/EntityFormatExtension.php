<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Twig\EntityExtension;
use Oro\Bundle\UIBundle\Twig\FormatExtension;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to apply default formatting for entity fields in sandboxes:
 *   - _entity_var
 */
class EntityFormatExtension extends AbstractExtension
{
    private const EXPR_WITHOUT_ERROR_MESSAGE =
        '{% if %val% is defined %}{{ _entity_var("%name%", %val%, %parent%) }}{% endif %}';
    private const EXPR_WITH_ERROR_MESSAGE    =
        '{% if %val% is defined %}{{ _entity_var("%name%", %val%, %parent%) }}{% else %}{{ %errMsg% }}{% endif %}';

    /** @var array [class name => [field name => formatter name or [formatter name, formatter arguments], ...], ...] */
    private $formatters = [];

    public function setFormatters(array $formatters): void
    {
        $this->formatters = $formatters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                '_entity_var',
                [$this, 'format'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            )
        ];
    }

    public function getSafeFormatExpression(
        string $name,
        string $path,
        string $parentPath,
        string $notDefinedMessage = null
    ): string {
        if ($notDefinedMessage) {
            return strtr(
                self::EXPR_WITH_ERROR_MESSAGE,
                [
                    '%name%'   => $name,
                    '%val%'    => $path,
                    '%parent%' => $parentPath,
                    '%errMsg%' => json_encode($notDefinedMessage)
                ]
            );
        }

        return strtr(
            self::EXPR_WITHOUT_ERROR_MESSAGE,
            [
                '%name%'   => $name,
                '%val%'    => $path,
                '%parent%' => $parentPath
            ]
        );
    }

    /**
     * @param Environment       $environment
     * @param string            $name
     * @param mixed             $value
     * @param mixed             $parentValue
     *
     * @return mixed
     */
    public function format(Environment $environment, string $name, $value, $parentValue)
    {
        if (\is_object($parentValue)) {
            $parentClass = ClassUtils::getClass($parentValue);
            if (isset($this->formatters[$parentClass][$name])) {
                return $this->formatByFormatter($environment, $value, $this->formatters[$parentClass][$name]);
            }
        }

        if (\is_object($value)) {
            return $this->formatObject($environment, $value);
        }

        return $this->formatScalar($environment, $value);
    }

    /**
     * @param Environment       $environment
     * @param mixed             $value
     * @param mixed             $formatter
     *
     * @return mixed
     */
    protected function formatByFormatter(Environment $environment, $value, $formatter)
    {
        /** @var FormatExtension $formatExtension */
        $formatExtension = $environment->getExtension(FormatExtension::class);

        if (!\is_array($formatter)) {
            return $formatExtension->format($value, $formatter);
        }

        if (\count($formatter) > 1) {
            return $formatExtension->format($value, $formatter[0], $formatter[1]);
        }

        return $formatExtension->format($value, $formatter[0]);
    }

    /**
     * @param Environment $environment
     * @param object            $value
     *
     * @return mixed
     */
    protected function formatObject(Environment $environment, $value)
    {
        /** @var EntityExtension $entityExtension */
        $entityExtension = $environment->getExtension(EntityExtension::class);

        return $entityExtension->getEntityName($value);
    }

    /**
     * @param Environment $environment
     * @param mixed             $value
     *
     * @return mixed
     */
    protected function formatScalar(Environment $environment, $value)
    {
        /** @var HtmlTagExtension $htmlTagExtension */
        $htmlTagExtension = $environment->getExtension(HtmlTagExtension::class);

        return $htmlTagExtension->htmlSanitize($value);
    }
}

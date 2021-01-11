<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\ExtendFallback;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Generates getters and setters for default fallback fields.
 */
class DefaultFallbackGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /** @var array [class name => [singular field name => field name, ...], ...] */
    private array $fieldMap;

    private Inflector $inflector;

    /**
     * @param array $fieldMap [class name => [singular field name => field name, ...], ...]
     */
    public function __construct(array $fieldMap, Inflector $inflector)
    {
        $this->fieldMap = $fieldMap;
        $this->inflector = $inflector;
    }

    public function supports(array $schema): bool
    {
        return isset($schema['class'], $this->fieldMap[$schema['class']]);
    }

    public function generate(array $schema, ClassGenerator $class): void
    {
        if (!$this->supports($schema)) {
            return;
        }

        $fields = $this->fieldMap[$schema['class']];
        if (empty($fields)) {
            return;
        }

        $class->setExtends(ExtendFallback::class);

        foreach ($fields as $singularName => $fieldName) {
            $this->generateGetter($singularName, $fieldName, $class);
            $this->generateDefaultGetter($singularName, $fieldName, $class);
            $this->generateDefaultSetter($singularName, $fieldName, $class);
        }
    }

    /**
     * Generates code for a getter method
     */
    protected function generateGetter(string $singularName, string $fieldName, ClassGenerator $class): void
    {
        $class->addMethod($this->getMethodName($singularName, 'get'))
            ->addBody(\sprintf('return $this->getFallbackValue($this->%s, $localization);', $fieldName))
            ->addComment(
                $this->generateDocblock(
                    [\sprintf('\%s|null', Localization::class) =>'$localization'],
                    \sprintf('\%s|null', LocalizedFallbackValue::class)
                )
            )
            ->addParameter('localization')->setType(Localization::class)->setDefaultValue(null);
    }

    /**
     * Generates code for the default getter method
     */
    protected function generateDefaultGetter(string $singularName, string $fieldName, ClassGenerator $class): void
    {
        $class->addMethod($this->getMethodName($singularName, 'getDefault'))
            ->addBody(\sprintf('return $this->getDefaultFallbackValue($this->%s);', $fieldName))
            ->addComment($this->generateDocblock([], \sprintf('\%s|null', LocalizedFallbackValue::class)));
    }

    /**
     * Generates code for the default setter method
     */
    protected function generateDefaultSetter(string $singularName, string $fieldName, ClassGenerator $class): void
    {
        $class->addMethod($this->getMethodName($singularName, 'setDefault'))
            ->addBody(\sprintf('return $this->setDefaultFallbackValue($this->%s, $value);', $fieldName))
            ->addComment($this->generateDocblock(['string' =>  '$value'], '$this'))
            ->addParameter('value');
    }

    protected function generateDocblock(array $params, string $return = null): string
    {
        $parts = [];

        foreach ($params as $type => $param) {
            $parts[] = \sprintf('@param %s %s', $type, $param);
        }

        if ($return) {
            $parts[] = \sprintf('@return %s', $return);
        }

        return \implode("\n", $parts);
    }

    protected function getMethodName(string $fieldName, string $prefix): string
    {
        return $prefix . \ucfirst($this->inflector->camelize($fieldName));
    }
}

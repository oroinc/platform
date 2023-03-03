<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Component\PhpUtils\ClassGenerator;
use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * The main extension of the entity generator. This extension is responsible for generation of an extend entity skeleton
 * and all extend fields and relations.
 */
class ExtendEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    private Inflector $inflector;

    private EnglishInflector $symfonyInflector;

    public function __construct(Inflector $inflector)
    {
        $this->inflector = $inflector;
        $this->symfonyInflector = new EnglishInflector();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supports(array $schema): bool
    {
        return true;
    }

    public function generate(array $schema, ClassGenerator $class): void
    {
        if (!empty($schema['inherit'])) {
            $class->addExtend($schema['inherit']);
        }
        if (str_contains($schema['class'], ExtendClassLoadingUtils::getEntityNamespace())) {
            $class->addImplement(ExtendEntityInterface::class);
            $class->addTrait(ExtendEntityTrait::class);
        }
    }

    protected function getSingular(string $fieldName): string
    {
        $singular = $this->symfonyInflector->singularize($this->inflector->classify($fieldName));
        if (\is_array($singular)) {
            $singular = \reset($singular);
        }

        return $singular;
    }
}

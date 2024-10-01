<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Tools;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\VirtualReflectionMethod;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendAutocompleteGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use phpDocumentor\Reflection\DocBlockFactory;

class ExtendAutocompleteGeneratorTest extends WebTestCase
{
    private DocBlockFactory $docBlockFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    public function testGenerate()
    {
        /** @var ExtendAutocompleteGenerator $generator */
        $generator = self::getContainer()->get('oro_entity_extend.autocomplete_class_generator');
        $generator->generate();
        $autocompleteFile = ExtendClassLoadingUtils::getAutocompleteClassesPath(self::$kernel->getCacheDir());

        self::assertFileExists($autocompleteFile);

        $fileContents = \file_get_contents($autocompleteFile);
        preg_match_all('/^trait (.+)/m', $fileContents, $matches);
        $traitNames = $matches[1];

        foreach ($traitNames as $traitName) {
            $traitFQN = 'Extend\Entity\Autocomplete\\' . $traitName;

            $docBlock = $this->docBlockFactory->create(new \ReflectionClass($traitFQN));

            /** @var See $see */
            $see = $docBlock->getTagsByName('see');
            $entityClass = (string)$see[0]->getReference();
            $reflectionClass = new \ReflectionClass($entityClass);
            if ($this->hasRequiredConstructorArguments($reflectionClass)) {
                $matchedConstructorArgs = $this->matchConstructorArgs($entityClass);
                if (empty($matchedConstructorArgs)) {
                    continue;
                }
                $object = new $entityClass(...$matchedConstructorArgs);
            } else {
                $object = new $entityClass();
            }
            $this->checkAutocompleteProperties($docBlock, $object);
            $this->checkAutocompleteMethods($docBlock, $object);
        }
    }

    private function checkAutocompleteProperties(DocBlock $docBlock, object $object): void
    {
        $properties = $docBlock->getTagsByName('property');
        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Property $property */
        foreach ($properties as $property) {
            $object->{$property->getVariableName()};
        }
    }

    private function checkAutocompleteMethods(DocBlock $docBlock, object $object): void
    {
        $methods = $docBlock->getTagsByName('method');
        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Method $method */
        foreach ($methods as $method) {
            $methodName = $method->getMethodName();
            if ($this->isMethodSkipped($object, $methodName)) {
                return;
            }
            $reflectionMethod = new VirtualReflectionMethod($object, $methodName);
            $arguments = [];
            if ($this->hasRequiredMethodArguments($reflectionMethod)) {
                $methodArguments = $method->getArguments();
                foreach ($methodArguments as $argument) {
                    if (!isset($argument['type'])) {
                        continue;
                    }
                    $actualType = (string)$argument['type'];
                    $argumentByType = $this->getArgumentByType($actualType);
                    if (null !== $argumentByType) {
                        $arguments[] = $argumentByType;
                    }
                }
                if (count($arguments) !== count($methodArguments)) {
                    continue;
                }
            }
            $object->$methodName(...$arguments);
        }
    }

    private function hasRequiredConstructorArguments(\ReflectionClass $reflection): bool
    {
        $constructor = $reflection->getConstructor();

        return $this->hasRequiredMethodArguments($constructor);
    }

    private function hasRequiredMethodArguments(?\ReflectionMethod $reflectionMethod): bool
    {
        if (!$reflectionMethod) {
            return false;
        }

        return $reflectionMethod->getNumberOfRequiredParameters() > 0;
    }

    private function matchConstructorArgs(string $className): array
    {
        return match ($className) {
            '\\' . Country::class => ['EN'],
            '\\' . CustomerUserSettings::class => [new Website()],
            default => []
        };
    }

    private function isMethodSkipped(object $object, string $methodName): bool
    {
        // skipping the actual method that overrides extends
        if ($methodName === 'setSerializedData') {
            return method_exists($object, $methodName);
        }
        // skip all remove methods
        return str_starts_with($methodName, 'remove');
    }

    private function getArgumentByType(string $type): mixed
    {
        if (str_starts_with($type, '?')) {
            $type = ltrim($type, '?');
        }

        return match ($type) {
            'int' => 123,
            'bool' => true,
            'float' => 10.5,
            'string' => 'test',
            'array' => [],
            default => null
        };
    }
}

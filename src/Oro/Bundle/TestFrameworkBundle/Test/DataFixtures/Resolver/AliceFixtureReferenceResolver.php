<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver;

use Nelmio\Alice\Definition\Fixture\FixtureId;
use Nelmio\Alice\Definition\Object\CompleteObject;
use Nelmio\Alice\Definition\Value\FixtureReferenceValue;
use Nelmio\Alice\Definition\ValueInterface;
use Nelmio\Alice\FixtureIdInterface;
use Nelmio\Alice\FixtureInterface;
use Nelmio\Alice\Generator\GenerationContext;
use Nelmio\Alice\Generator\ObjectGeneratorAwareInterface;
use Nelmio\Alice\Generator\ObjectGeneratorInterface;
use Nelmio\Alice\Generator\ResolvedFixtureSet;
use Nelmio\Alice\Generator\ResolvedValueWithFixtureSet;
use Nelmio\Alice\Generator\Resolver\Value\ChainableValueResolverInterface;
use Nelmio\Alice\IsAServiceTrait;
use Nelmio\Alice\Throwable\Exception\Generator\ObjectGenerator\ObjectGeneratorNotFoundExceptionFactory;
use Nelmio\Alice\Throwable\Exception\Generator\Resolver\CircularReferenceException;
use Nelmio\Alice\Throwable\Exception\Generator\Resolver\FixtureNotFoundExceptionFactory;
use Nelmio\Alice\Throwable\Exception\Generator\Resolver\UnresolvableValueException;
use Nelmio\Alice\Throwable\Exception\Generator\Resolver\UnresolvableValueExceptionFactory;

/**
 * Alice fixture reference resolver.
 *
 * @copyright 2012 Nelmio
 * @license https://github.com/nelmio/alice/blob/2.x/LICENSE MIT License
 * @link https://github.com/nelmio/alice/tree/2.x
 *
 * Added clear() method for cleaning $incompleteObjects property value
 * Added getInstances() method to get all instances of class to clear them incomplete objects state after test
 */
final class AliceFixtureReferenceResolver implements ChainableValueResolverInterface, ObjectGeneratorAwareInterface
{
    use IsAServiceTrait;

    /**
     * @var ObjectGeneratorInterface|null
     */
    private $generator;

    /**
     * @var array
     */
    private $incompleteObjects = [];

    protected array $instances = [];

    public function __construct(?ObjectGeneratorInterface $generator = null)
    {
        $this->generator = $generator;
    }

    #[\Override]
    public function withObjectGenerator(ObjectGeneratorInterface $generator): self
    {
        $newResolver = new self($generator);
        $this->instances[] = $newResolver;

        return $newResolver;
    }

    #[\Override]
    public function canResolve(ValueInterface $value): bool
    {
        return $value instanceof FixtureReferenceValue;
    }

    public function clear(): void
    {
        $this->incompleteObjects = [];
    }

    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * @param FixtureReferenceValue $value
     *
     * @throws UnresolvableValueException
     */
    #[\Override]
    public function resolve(
        ValueInterface $value,
        FixtureInterface $fixture,
        ResolvedFixtureSet $fixtureSet,
        array $scope,
        GenerationContext $context
    ): ResolvedValueWithFixtureSet {
        if (null === $this->generator) {
            throw ObjectGeneratorNotFoundExceptionFactory::createUnexpectedCall(__METHOD__);
        }

        $referredFixtureId = $value->getValue();
        if ($referredFixtureId instanceof ValueInterface) {
            throw UnresolvableValueExceptionFactory::create($value);
        }

        $referredFixture = $this->getReferredFixture($referredFixtureId, $fixtureSet);

        return $this->resolveReferredFixture($referredFixture, $referredFixtureId, $fixtureSet, $context);
    }

    private function getReferredFixture(string $id, ResolvedFixtureSet $set): FixtureIdInterface
    {
        $fixtures = $set->getFixtures();
        if ($fixtures->has($id)) {
            return $fixtures->get($id);
        }

        return new FixtureId($id);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function resolveReferredFixture(
        FixtureIdInterface $referredFixture,
        string $referredFixtureId,
        ResolvedFixtureSet $fixtureSet,
        GenerationContext $context,
        ?bool $passIncompleteObject = null
    ): ResolvedValueWithFixtureSet {
        if ($fixtureSet->getObjects()->has($referredFixture)) {
            $referredObject = $fixtureSet->getObjects()->get($referredFixture);
            if (
                $referredObject instanceof CompleteObject
                || $passIncompleteObject
                || array_key_exists($referredFixtureId, $this->incompleteObjects)
            ) {
                $this->incompleteObjects[$referredFixtureId] = true;

                return new ResolvedValueWithFixtureSet(
                    $referredObject->getInstance(),
                    $fixtureSet
                );
            }
        }

        // Object is either not completely generated or has not been generated at all yet
        // Attempts to generate the fixture completely
        if (false === $referredFixture instanceof FixtureInterface) {
            throw FixtureNotFoundExceptionFactory::create($referredFixtureId);
        }

        try {
            $needsCompleteGeneration = $context->needsCompleteGeneration();

            // Attempts to provide a complete object whenever possible
            $passIncompleteObject
                ? $context->unmarkAsNeedsCompleteGeneration()
                : $context->markAsNeedsCompleteGeneration();

            $context->markIsResolvingFixture($referredFixtureId);
            $objects = $this->generator->generate($referredFixture, $fixtureSet, $context);
            $fixtureSet =  $fixtureSet->withObjects($objects);

            // Restore the context
            $needsCompleteGeneration
                ? $context->markAsNeedsCompleteGeneration()
                : $context->unmarkAsNeedsCompleteGeneration();

            return new ResolvedValueWithFixtureSet(
                $fixtureSet->getObjects()->get($referredFixture)->getInstance(),
                $fixtureSet
            );
        } catch (CircularReferenceException $exception) {
            if (
                false === $needsCompleteGeneration
                && null !== $passIncompleteObject
            ) {
                throw $exception;
            }

            $context->unmarkAsNeedsCompleteGeneration();

            // Could not completely generate the fixtures, fallback to generating an incomplete object
            return $this->resolveReferredFixture(
                $referredFixture,
                $referredFixtureId,
                $fixtureSet,
                $context,
                true
            );
        }
    }
}

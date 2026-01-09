<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

/**
 * Factory for creating Alice fixtures from class names or file paths.
 *
 * This factory creates fixture instances from either fixture class names or Alice YAML/PHP
 * fixture files, providing flexible fixture loading for functional tests.
 */
class AliceFixtureFactory implements FixtureFactoryInterface
{
    #[\Override]
    public function createFixture($fixtureId)
    {
        if (class_exists($fixtureId)) {
            return new $fixtureId();
        }
        if (file_exists($fixtureId)) {
            return new AliceFileFixture($fixtureId);
        }

        throw new \InvalidArgumentException(
            sprintf('The "%s" must be a class name or a file name.', $fixtureId)
        );
    }
}

<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

class AliceFixtureFactory implements FixtureFactoryInterface
{
    /**
     * {@inheritdoc}
     */
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

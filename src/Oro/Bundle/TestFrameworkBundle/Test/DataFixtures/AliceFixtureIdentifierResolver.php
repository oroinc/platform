<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Symfony\Component\HttpKernel\KernelInterface;

class AliceFixtureIdentifierResolver implements FixtureIdentifierResolverInterface
{
    /** @var KernelInterface */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveId($fixture)
    {
        if (is_object($fixture)) {
            if ($fixture instanceof AliceFileFixture) {
                $fileName = $fixture->getFileName();
                if ($fileName && '@' === $fileName[0]) {
                    $fileName = $this->kernel->locateResource($fileName);
                }

                return $fileName;
            }

            return get_class($fixture);
        }

        if (is_string($fixture)) {
            if ($fixture && '@' === $fixture[0]) {
                $fixture = $this->kernel->locateResource($fixture);
            }

            return $fixture;
        }

        throw new \InvalidArgumentException(
            sprintf('Expected argument of type "object or string", "%s" given.', gettype($fixture))
        );
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerInterface;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectNormalizerRegistryTest extends TestCase
{
    private ObjectNormalizerInterface&MockObject $normalizer1;
    private ObjectNormalizerInterface&MockObject $normalizer2;
    private ObjectNormalizerRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->normalizer1 = $this->createMock(ObjectNormalizerInterface::class);
        $this->normalizer2 = $this->createMock(ObjectNormalizerInterface::class);

        $this->registry = new ObjectNormalizerRegistry(
            [
                ['normalizer2', \DateTimeInterface::class, 'rest'],
                ['normalizer1', \DateTimeInterface::class, null]
            ],
            TestContainerBuilder::create()
                ->add('normalizer1', $this->normalizer1)
                ->add('normalizer2', $this->normalizer2)
                ->getContainer($this),
            new RequestExpressionMatcher()
        );
    }

    public function testGetObjectNormalizerWhenItExistsForSpecificRequestType(): void
    {
        self::assertSame(
            $this->normalizer2,
            $this->registry->getObjectNormalizer(new \DateTime(), new RequestType(['rest', 'json_api']))
        );
    }

    public function testGetObjectNormalizerWhenItDoesNotExistForSpecificRequestTypeButExistsForAnyRequestType(): void
    {
        self::assertSame(
            $this->normalizer1,
            $this->registry->getObjectNormalizer(new \DateTime(), new RequestType(['another']))
        );
    }

    public function testGetObjectNormalizerWhenItDoesNotExistForSpecificClass(): void
    {
        self::assertNull(
            $this->registry->getObjectNormalizer(new \stdClass(), new RequestType(['another']))
        );
    }
}

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\DataTransformer\Factory;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\CryptedDataTransformer;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CryptedDataTransformerFactoryTest extends TestCase
{
    private SymmetricCrypterInterface&MockObject $crypter;
    private LoggerInterface&MockObject $logger;
    private CryptedDataTransformerFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->factory = new CryptedDataTransformerFactory($this->crypter, $this->logger);
    }

    public function testCreate(): void
    {
        $transformer = new CryptedDataTransformer($this->crypter);
        $transformer->setLogger($this->logger);

        $actualTransformer = $this->factory->create();

        self::assertEquals($transformer, $actualTransformer);
    }
}

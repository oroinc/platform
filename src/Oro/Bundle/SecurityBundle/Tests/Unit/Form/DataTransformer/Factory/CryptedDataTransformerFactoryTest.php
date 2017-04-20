<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\DataTransformer\Factory;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\CryptedDataTransformer;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactory;
use Psr\Log\LoggerInterface;

class CryptedDataTransformerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CryptedDataTransformerFactory
     */
    private $factory;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $crypter;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->factory = new CryptedDataTransformerFactory($this->crypter, $this->logger);
    }

    public function testCreate()
    {
        $transformer = new CryptedDataTransformer($this->crypter);
        $transformer->setLogger($this->logger);

        $actualTransformer = $this->factory->create();

        static::assertEquals($transformer, $actualTransformer);
    }
}

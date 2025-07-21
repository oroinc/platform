<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Behat\Stub;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;

class PdfBuilderFactoryStub implements PdfBuilderFactoryInterface
{
    public function __construct(
        private readonly EntityNameResolver $entityNameResolver
    ) {
    }

    #[\Override]
    public function createPdfBuilder(?string $pdfOptionsPreset = null): PdfBuilderInterface
    {
        return new PdfBuilderStub($this->entityNameResolver);
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Provider\TranslationProvider;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var TranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $traslationRepository;

    /** @var TranslationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->traslationRepository = $this->createMock(TranslationRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($this->traslationRepository);

        $this->provider = new TranslationProvider($this->doctrine);
    }

    public function testGetMessageCatalogueByLocaleAndScope(): void
    {
        $locale = 'en';
        $scopes = ['scope1', 'scope2'];

        $this->traslationRepository->expects(self::once())
            ->method('findAllByLanguageAndScopes')
            ->with($locale, $scopes)
            ->willReturn([
                ['id' => 1, 'value' => 'trans1', 'key' => 'key1', 'domain' => 'domain1'],
                ['id' => 2, 'value' => 'trans2', 'key' => 'key2', 'domain' => 'domain2'],
                ['id' => 3, 'value' => 'trans3', 'key' => 'key3', 'domain' => 'domain1'],
                ['id' => 4, 'value' => 'trans4', 'key' => 'key4', 'domain' => 'domain3'],
                ['id' => 5, 'value' => 'trans5', 'key' => 'key5', 'domain' => 'domain1'],
                ['id' => 6, 'value' => 'trans6', 'key' => 'key6', 'domain' => 'domain2'],
                ['id' => 7, 'value' => 'trans7', 'key' => 'key7', 'domain' => 'domain1'],
                ['id' => 8, 'value' => 'trans8', 'key' => 'key8', 'domain' => 'domain3'],
                ['id' => 9, 'value' => 'trans9', 'key' => 'key9', 'domain' => 'domain2']
            ]);

        $result = $this->provider->getMessageCatalogueByLocaleAndScope($locale, $scopes);
        $expectedResult = new MessageCatalogue($locale);
        $expectedResult->add(
            ['key1' => 'trans1', 'key3' => 'trans3', 'key5' => 'trans5', 'key7' => 'trans7'],
            'domain1'
        );
        $expectedResult->add(
            ['key2' => 'trans2', 'key6' => 'trans6', 'key9' => 'trans9'],
            'domain2'
        );
        $expectedResult->add(
            ['key4' => 'trans4', 'key8' => 'trans8'],
            'domain3'
        );

        self::assertEquals($expectedResult, $result);
    }
}

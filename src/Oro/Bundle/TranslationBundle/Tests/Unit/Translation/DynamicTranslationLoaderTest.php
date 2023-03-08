<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationLoader;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class DynamicTranslationLoaderTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private DynamicTranslationLoader $loader;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->loader = new DynamicTranslationLoader($doctrine);
    }

    public function testLoadTranslations(): void
    {
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.code AS code_0, o1_.domain AS domain_1, o1_."key" AS key_2, o2_.value AS value_3'
            . ' FROM oro_translation o2_'
            . ' INNER JOIN oro_language o0_ ON o2_.language_id = o0_.id'
            . ' INNER JOIN oro_translation_key o1_ ON o2_.translation_key_id = o1_.id'
            . ' WHERE o0_.code IN (?, ?) AND o2_.scope > ?'
            . ' ORDER BY o2_.scope DESC',
            [
                [
                    'code_0'   => 'en',
                    'domain_1' => 'messages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN)'
                ],
                [
                    'code_0'   => 'en',
                    'domain_1' => 'messages',
                    'key_2'    => 'bar',
                    'value_3'  => 'bar (EN)'
                ],
                [
                    'code_0'   => 'en',
                    'domain_1' => 'jsmessages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN) (JS)'
                ],
                [
                    'code_0'   => 'en_US',
                    'domain_1' => 'messages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN_US) (scope=installed)'
                ],
                [
                    'code_0'   => 'en_US',
                    'domain_1' => 'messages',
                    'key_2'    => 'bar',
                    'value_3'  => 'bar (EN_US) (scope=installed)'
                ],
                [
                    'code_0'   => 'en_US',
                    'domain_1' => 'messages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN_US)'
                ],
            ],
            [1 => 'en_US', 2 => 'en', 3 => Translation::SCOPE_SYSTEM],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_STR, 3 => \PDO::PARAM_INT]
        );

        self::assertEquals(
            [
                'en'    => [
                    'messages'   => [
                        'foo' => 'foo (EN)',
                        'bar' => 'bar (EN)'
                    ],
                    'jsmessages' => [
                        'foo' => 'foo (EN) (JS)'
                    ]
                ],
                'en_US' => [
                    'messages' => [
                        'foo' => 'foo (EN_US)',
                        'bar' => 'bar (EN_US) (scope=installed)'
                    ]
                ]
            ],
            $this->loader->loadTranslations(['en_US', 'en'], false)
        );
    }

    public function testLoadTranslationsIncludeSystem(): void
    {
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.code AS code_0, o1_.domain AS domain_1, o1_."key" AS key_2, o2_.value AS value_3'
            . ' FROM oro_translation o2_'
            . ' INNER JOIN oro_language o0_ ON o2_.language_id = o0_.id'
            . ' INNER JOIN oro_translation_key o1_ ON o2_.translation_key_id = o1_.id'
            . ' WHERE o0_.code IN (?, ?)'
            . ' ORDER BY o2_.scope DESC',
            [
                [
                    'code_0'   => 'en',
                    'domain_1' => 'messages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN)'
                ],
                [
                    'code_0'   => 'en',
                    'domain_1' => 'messages',
                    'key_2'    => 'bar',
                    'value_3'  => 'bar (EN)'
                ],
                [
                    'code_0'   => 'en',
                    'domain_1' => 'jsmessages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN) (JS)'
                ],
                [
                    'code_0'   => 'en_US',
                    'domain_1' => 'messages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN_US) (scope=installed)'
                ],
                [
                    'code_0'   => 'en_US',
                    'domain_1' => 'messages',
                    'key_2'    => 'bar',
                    'value_3'  => 'bar (EN_US) (scope=installed)'
                ],
                [
                    'code_0'   => 'en_US',
                    'domain_1' => 'messages',
                    'key_2'    => 'foo',
                    'value_3'  => 'foo (EN_US)'
                ],
            ],
            [1 => 'en_US', 2 => 'en'],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_STR]
        );

        self::assertEquals(
            [
                'en'    => [
                    'messages'   => [
                        'foo' => 'foo (EN)',
                        'bar' => 'bar (EN)'
                    ],
                    'jsmessages' => [
                        'foo' => 'foo (EN) (JS)'
                    ]
                ],
                'en_US' => [
                    'messages' => [
                        'foo' => 'foo (EN_US)',
                        'bar' => 'bar (EN_US) (scope=installed)'
                    ]
                ]
            ],
            $this->loader->loadTranslations(['en_US', 'en'], true)
        );
    }
}

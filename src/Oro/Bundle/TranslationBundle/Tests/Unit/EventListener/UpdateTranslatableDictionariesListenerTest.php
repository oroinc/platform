<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\EventListener\UpdateTranslatableDictionariesListener;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class UpdateTranslatableDictionariesListenerTest extends OrmTestCase
{
    private const CHANGED = 0;
    private const REMOVED = 1;

    private EntityManagerInterface $em;
    private UpdateTranslatableDictionariesListener $listener;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->listener = new UpdateTranslatableDictionariesListener();
        $this->listener->addEntity(Region::class, 'code', 'region.', 'combinedCode');
        $this->listener->addEntity(Country::class, 'iso2', 'country.iso2.', 'iso2Code');
        $this->listener->addEntity(Country::class, 'iso3', 'country.iso3.', 'iso3Code');
    }

    private function getTranslation(string $key, string $domain = 'entities', string $language = 'en_US'): Translation
    {
        $translationKeyEntity = new TranslationKey();
        $translationKeyEntity->setKey($key);
        $translationKeyEntity->setDomain($domain);

        $languageEntity = new Language();
        $languageEntity->setCode($language);

        $translationEntity = new Translation();
        $translationEntity->setTranslationKey($translationKeyEntity);
        $translationEntity->setLanguage($languageEntity);
        $translationEntity->setValue('trans: ' . $key);

        return $translationEntity;
    }

    private static function getData(UpdateTranslatableDictionariesListener $listener): array
    {
        return ReflectionUtil::getPropertyValue($listener, 'data');
    }

    /**
     * @dataProvider postPersistAndPostUpdateDataProvider
     */
    public function testPostPersist(array $translations, array $scheduledTranslations): void
    {
        /** @var Translation $translation */
        foreach ($translations as $translation) {
            $this->listener->postPersist($translation);
        }
        self::assertEquals($scheduledTranslations, self::getData($this->listener));
    }

    /**
     * @dataProvider postPersistAndPostUpdateDataProvider
     */
    public function testPostUpdate(array $translations, array $scheduledTranslations): void
    {
        /** @var Translation $translation */
        foreach ($translations as $translation) {
            $this->listener->postUpdate($translation);
        }
        self::assertEquals($scheduledTranslations, self::getData($this->listener));
    }

    public function postPersistAndPostUpdateDataProvider(): array
    {
        return [
            'not applicable translation key'           => [
                [$this->getTranslation('some.label')],
                []
            ],
            'not applicable translation domain'        => [
                [$this->getTranslation('region.us_ny', 'messages')],
                []
            ],
            'entity with one translatable field'       => [
                [$this->getTranslation('region.us_ny')],
                [
                    self::CHANGED => [
                        Region::class => [
                            'code' => [
                                'en_US' => ['us_ny' => 'trans: region.us_ny']
                            ]
                        ]
                    ]
                ]
            ],
            'entity with several translatable field'   => [
                [$this->getTranslation('country.iso3.us')],
                [
                    self::CHANGED => [
                        Country::class => [
                            'iso3' => [
                                'en_US' => ['us' => 'trans: country.iso3.us']
                            ]
                        ]
                    ]
                ]
            ],
            'several entities with translatable field' => [
                [
                    $this->getTranslation('country.iso2.us'),
                    $this->getTranslation('country.iso3.us'),
                    $this->getTranslation('region.us_ny'),
                    $this->getTranslation('region.us_la'),
                    $this->getTranslation('country.iso2.us'),
                    $this->getTranslation('region.us_ny'),
                ],
                [
                    self::CHANGED => [
                        Country::class => [
                            'iso2' => [
                                'en_US' => ['us' => 'trans: country.iso2.us']
                            ],
                            'iso3' => [
                                'en_US' => ['us' => 'trans: country.iso3.us']
                            ]
                        ],
                        Region::class  => [
                            'code' => [
                                'en_US' => ['us_ny' => 'trans: region.us_ny', 'us_la' => 'trans: region.us_la']
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider postRemoveDataProvider
     */
    public function testPostRemove(array $translations, array $scheduledTranslations): void
    {
        /** @var Translation $translation */
        foreach ($translations as $translation) {
            $this->listener->postRemove($translation);
        }
        self::assertEquals($scheduledTranslations, self::getData($this->listener));
    }

    public function postRemoveDataProvider(): array
    {
        return [
            'not applicable translation key'           => [
                [$this->getTranslation('some.label')],
                []
            ],
            'not applicable translation domain'        => [
                [$this->getTranslation('region.us_ny', 'messages')],
                []
            ],
            'entity with one translatable field'       => [
                [$this->getTranslation('region.us_ny')],
                [
                    self::REMOVED => [
                        Region::class => [
                            'code' => [
                                'en_US' => ['us_ny' => null]
                            ]
                        ]
                    ]
                ]
            ],
            'entity with several translatable field'   => [
                [$this->getTranslation('country.iso3.us')],
                [
                    self::REMOVED => [
                        Country::class => [
                            'iso3' => [
                                'en_US' => ['us' => null]
                            ]
                        ]
                    ]
                ]
            ],
            'several entities with translatable field' => [
                [
                    $this->getTranslation('country.iso2.us'),
                    $this->getTranslation('country.iso3.us'),
                    $this->getTranslation('region.us_ny'),
                    $this->getTranslation('region.us_la'),
                    $this->getTranslation('country.iso2.us'),
                    $this->getTranslation('region.us_ny'),
                ],
                [
                    self::REMOVED => [
                        Country::class => [
                            'iso2' => [
                                'en_US' => ['us' => null]
                            ],
                            'iso3' => [
                                'en_US' => ['us' => null]
                            ]
                        ],
                        Region::class  => [
                            'code' => [
                                'en_US' => ['us_ny' => null, 'us_la' => null]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    public function testPostFlushForNewAndUpdatedTranslations(): void
    {
        $this->listener->postPersist($this->getTranslation('country.iso2.us'));
        $this->listener->postPersist($this->getTranslation('country.iso2.fr'));
        $this->listener->postUpdate($this->getTranslation('country.iso3.us'));
        $this->listener->postUpdate($this->getTranslation('region.us_ny'));

        $this->addQueryExpectation(
            'UPDATE oro_dictionary_country_trans SET content = ?'
            . ' WHERE foreign_key = ? AND locale = ? AND object_class = ? AND field = ?',
            null,
            [1 => 'trans: country.iso2.us', 2 => 'us', 3 => 'en_US', 4 => Country::class, 5 => 'iso2'],
            array_fill(1, 5, \PDO::PARAM_STR),
            1
        );
        $this->addQueryExpectation(
            'UPDATE oro_dictionary_country_trans SET content = ?'
            . ' WHERE foreign_key = ? AND locale = ? AND object_class = ? AND field = ?',
            null,
            [1 => 'trans: country.iso2.fr', 2 => 'fr', 3 => 'en_US', 4 => Country::class, 5 => 'iso2'],
            array_fill(1, 5, \PDO::PARAM_STR),
            0
        );
        $this->addQueryExpectation(
            'INSERT INTO oro_dictionary_country_trans (foreign_key, locale, object_class, field, content)'
            . ' VALUES (?, ?, ?, ?, ?)',
            null,
            [1 => 'fr', 2 => 'en_US', 3 => Country::class, 4 => 'iso2', 5 => 'trans: country.iso2.fr'],
            array_fill(1, 5, \PDO::PARAM_STR)
        );
        $this->addQueryExpectation(
            'UPDATE oro_dictionary_country_trans SET content = ?'
            . ' WHERE foreign_key = ? AND locale = ? AND object_class = ? AND field = ?',
            null,
            [1 => 'trans: country.iso3.us', 2 => 'us', 3 => 'en_US', 4 => Country::class, 5 => 'iso3'],
            array_fill(1, 5, \PDO::PARAM_STR),
            1
        );
        $this->addQueryExpectation(
            'UPDATE oro_dictionary_region_trans SET content = ?'
            . ' WHERE foreign_key = ? AND locale = ? AND object_class = ? AND field = ?',
            null,
            [1 => 'trans: region.us_ny', 2 => 'us_ny', 3 => 'en_US', 4 => Region::class, 5 => 'code'],
            array_fill(1, 5, \PDO::PARAM_STR),
            1
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->listener->postFlush(new PostFlushEventArgs($this->em));
        self::assertSame([], self::getData($this->listener));
    }

    public function testPostFlushForRemovedTranslations(): void
    {
        $this->listener->postRemove($this->getTranslation('country.iso2.us'));
        $this->listener->postRemove($this->getTranslation('country.iso3.us'));
        $this->listener->postRemove($this->getTranslation('country.iso2.fr'));
        $this->listener->postRemove($this->getTranslation('region.us_ny'));

        $this->addQueryExpectation(
            'DELETE FROM oro_dictionary_country_trans'
            . ' WHERE foreign_key IN (?, ?) AND locale = ? AND object_class = ? AND field = ?',
            null,
            [1 => 'us', 2 => 'fr', 3 => 'en_US', 4 => Country::class, 5 => 'iso2'],
            array_fill(1, 5, \PDO::PARAM_STR)
        );
        $this->addQueryExpectation(
            'DELETE FROM oro_dictionary_country_trans'
            . ' WHERE foreign_key IN (?) AND locale = ? AND object_class = ? AND field = ?',
            null,
            [1 => 'us', 2 => 'en_US', 3 => Country::class, 4 => 'iso3'],
            array_fill(1, 4, \PDO::PARAM_STR)
        );
        $this->addQueryExpectation(
            'DELETE FROM oro_dictionary_region_trans'
            . ' WHERE foreign_key IN (?) AND locale = ? AND object_class = ? AND field = ?',
            null,
            [1 => 'us_ny', 2 => 'en_US', 3 => Region::class, 4 => 'code'],
            array_fill(1, 4, \PDO::PARAM_STR)
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->listener->postFlush(new PostFlushEventArgs($this->em));
        self::assertSame([], self::getData($this->listener));
    }

    public function testOnClear(): void
    {
        $this->listener->postPersist($this->getTranslation('country.iso2.us'));
        $this->listener->onClear();
        self::assertSame([], self::getData($this->listener));
    }
}

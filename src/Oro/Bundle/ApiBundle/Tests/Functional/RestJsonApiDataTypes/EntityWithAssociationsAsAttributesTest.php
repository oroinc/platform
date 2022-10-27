<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDataTypes;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestArticle;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestMagazine;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityWithAssociationsAsAttributesTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/test_magazine.yml']);
    }

    private function getArticleId(string $headline): int
    {
        /** @var TestArticle|null $article */
        $article = $this->getEntityManager()->getRepository(TestArticle::class)
            ->findOneBy(['headline' => $headline]);
        if (null === $article) {
            throw new \RuntimeException(sprintf('The article "%s" not found.', $headline));
        }

        return $article->getId();
    }

    private function assertArticleExists(string $headline): void
    {
        /** @var TestArticle|null $article */
        $article = $this->getEntityManager()->getRepository(TestArticle::class)
            ->findOneBy(['headline' => $headline]);
        self::assertTrue(null !== $article, sprintf('The article "%s" does not exist.', $headline));
    }

    public function testGet()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Article 1',
                                'body'     => 'Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Article 2',
                                'body'     => 'Article 2 Body'
                            ],
                            [
                                'id'       => '@article3->id',
                                'headline' => 'Article 3',
                                'body'     => 'Article 3 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Article 1',
                            'body'     => 'Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithEmptyData()
    {
        $magazineId = $this->getReference('magazine2')->getId();
        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 2',
                        'articles'    => [],
                        'bestArticle' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociation()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'        => 'Updated Magazine 1',
                    'bestArticle' => [
                        'headline' => 'Updated Article 1',
                        'body'     => 'Updated Article 1 Body'
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Updated Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Updated Article 1',
                                'body'     => 'Updated Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Article 2',
                                'body'     => 'Article 2 Body'
                            ],
                            [
                                'id'       => '@article3->id',
                                'headline' => 'Article 3',
                                'body'     => 'Article 3 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenSubmittedOnlyPartOfFields()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => [
                        'headline' => 'Updated Article 1'
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Updated Article 1',
                                'body'     => 'Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Article 2',
                                'body'     => 'Article 2 Body'
                            ],
                            [
                                'id'       => '@article3->id',
                                'headline' => 'Article 3',
                                'body'     => 'Article 3 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Updated Article 1',
                            'body'     => 'Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenPreviousValueIsNull()
    {
        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => [
                        'headline' => 'Updated New Article',
                        'body'     => 'Updated New Article Body'
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );
        $newArticleId = $this->getArticleId('Updated New Article');

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 2',
                        'articles'    => [],
                        'bestArticle' => [
                            'id'       => $newArticleId,
                            'headline' => 'Updated New Article',
                            'body'     => 'Updated New Article Body'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenNewValueIsEmptyArray()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => []
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Article 1',
                                'body'     => 'Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Article 2',
                                'body'     => 'Article 2 Body'
                            ],
                            [
                                'id'       => '@article3->id',
                                'headline' => 'Article 3',
                                'body'     => 'Article 3 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Article 1',
                            'body'     => 'Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateForToOneAssociationWhenNewValueIsEmptyArrayAndPreviousValueIsNull()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'bestArticle' => [
                        'fields' => [
                            'id'       => [
                                'form_options' => [
                                    'mapped' => false
                                ]
                            ],
                            'headline' => [
                                'form_options' => [
                                    'constraints' => [['NotBlank' => []]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => []
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/bestArticle/headline']
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenNewValueIsEmptyArrayAndPreviousValueIsNullAndRequiredIsFalse()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'bestArticle' => [
                        'form_options' => [
                            'required' => false
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => []
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 2',
                        'articles'    => [],
                        'bestArticle' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateForToOneAssociationWhenNewValueIsNull()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'bestArticle' => [
                        'fields' => [
                            'id'       => [
                                'form_options' => [
                                    'mapped' => false
                                ]
                            ],
                            'headline' => [
                                'form_options' => [
                                    'constraints' => [['NotBlank' => []]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => null
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/bestArticle/headline']
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenNewValueIsNullAndRequiredIsFalse()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'bestArticle' => [
                        'form_options' => [
                            'required' => false
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => null
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Article 1',
                                'body'     => 'Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Article 2',
                                'body'     => 'Article 2 Body'
                            ],
                            [
                                'id'       => '@article3->id',
                                'headline' => 'Article 3',
                                'body'     => 'Article 3 Body'
                            ]
                        ],
                        'bestArticle' => null
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 1');
    }

    public function testTryToUpdateForToOneAssociationWhenNewValueIsNullAndPreviousValueIsNull()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'bestArticle' => [
                        'fields' => [
                            'id'       => [
                                'form_options' => [
                                    'mapped' => false
                                ]
                            ],
                            'headline' => [
                                'form_options' => [
                                    'constraints' => [['NotBlank' => []]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => null
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/bestArticle/headline']
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenNewValueIsNullAndPreviousValueIsNullAndRequiredIsFalse()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'bestArticle' => [
                        'form_options' => [
                            'required' => false
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => null
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 2',
                        'articles'    => [],
                        'bestArticle' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociation()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        [
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ],
                        [
                            'headline' => 'Updated Article 2',
                            'body'     => 'Updated Article 2 Body'
                        ]
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Updated Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Updated Article 1',
                                'body'     => 'Updated Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Updated Article 2',
                                'body'     => 'Updated Article 2 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 3');
    }

    public function testUpdateForToManyAssociationWhenSubmittedOnlyPartOfFields()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => [
                        [
                            'headline' => 'Updated Article 1'
                        ],
                        [
                            'headline' => 'Updated Article 2'
                        ]
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Updated Article 1',
                                'body'     => 'Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Updated Article 2',
                                'body'     => 'Article 2 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Updated Article 1',
                            'body'     => 'Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 3');
    }

    public function testUpdateForToManyAssociationWhenPreviousValueIsEmpty()
    {
        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => [
                        [
                            'headline' => 'Updated New Article 1',
                            'body'     => 'Updated New Article 1 Body'
                        ],
                        [
                            'headline' => 'Updated New Article 2',
                            'body'     => 'Updated New Article 2 Body'
                        ]
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );
        $newArticle1Id = $this->getArticleId('Updated New Article 1');
        $newArticle2Id = $this->getArticleId('Updated New Article 2');

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 2',
                        'articles'    => [
                            [
                                'id'       => $newArticle1Id,
                                'headline' => 'Updated New Article 1',
                                'body'     => 'Updated New Article 1 Body'
                            ],
                            [
                                'id'       => $newArticle2Id,
                                'headline' => 'Updated New Article 2',
                                'body'     => 'Updated New Article 2 Body'
                            ]
                        ],
                        'bestArticle' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociationWhenNewValueIsEmpty()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => []
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Magazine 1',
                        'articles'    => [],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Article 1',
                            'body'     => 'Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 1');
        $this->assertArticleExists('Article 2');
        $this->assertArticleExists('Article 3');
    }

    public function testUpdateForToManyAssociationWhenOneOfItemsIsEmptyArray()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        [
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ],
                        []
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Updated Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Updated Article 1',
                                'body'     => 'Updated Article 1 Body'
                            ],
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Article 2',
                                'body'     => 'Article 2 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 3');
    }

    public function testTryToUpdateForToManyAssociationWhenFirstItemIsNull()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'articles' => [
                        'fields' => [
                            'id'       => [
                                'form_options' => [
                                    'mapped' => false
                                ]
                            ],
                            'headline' => [
                                'form_options' => [
                                    'constraints' => [['NotBlank' => []]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        null,
                        [
                            'headline' => 'Updated Article 2',
                            'body'     => 'Updated Article 2 Body'
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/articles/0/headline']
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociationWhenFirstItemIsNullAndRequiredIsFalse()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'articles' => [
                        'form_options' => [
                            'entry_options' => [
                                'required' => false
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        null,
                        [
                            'headline' => 'Updated Article 2',
                            'body'     => 'Updated Article 2 Body'
                        ]
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Updated Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article2->id',
                                'headline' => 'Updated Article 2',
                                'body'     => 'Updated Article 2 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Article 1',
                            'body'     => 'Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 1');
        $this->assertArticleExists('Article 3');
    }

    public function testTryToUpdateForToManyAssociationWhenMiddleItemIsNull()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'articles' => [
                        'fields' => [
                            'id'       => [
                                'form_options' => [
                                    'mapped' => false
                                ]
                            ],
                            'headline' => [
                                'form_options' => [
                                    'constraints' => [['NotBlank' => []]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        [
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ],
                        null,
                        [
                            'headline' => 'Updated Article 3',
                            'body'     => 'Updated Article 3 Body'
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/articles/1/headline']
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociationWhenMiddleItemIsNullAndRequiredIsFalse()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'articles' => [
                        'form_options' => [
                            'entry_options' => [
                                'required' => false
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        [
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ],
                        null,
                        [
                            'headline' => 'Updated Article 3',
                            'body'     => 'Updated Article 3 Body'
                        ]
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Updated Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Updated Article 1',
                                'body'     => 'Updated Article 1 Body'
                            ],
                            [
                                'id'       => '@article3->id',
                                'headline' => 'Updated Article 3',
                                'body'     => 'Updated Article 3 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 2');
    }

    public function testTryToUpdateForToManyAssociationWhenLastItemIsNull()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'articles' => [
                        'fields' => [
                            'id'       => [
                                'form_options' => [
                                    'mapped' => false
                                ]
                            ],
                            'headline' => [
                                'form_options' => [
                                    'constraints' => [['NotBlank' => []]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        [
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ],
                        null
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/articles/1/headline']
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociationWhenLastItemIsNullAndRequiredIsFalse()
    {
        $this->appendEntityConfig(
            TestMagazine::class,
            [
                'fields' => [
                    'articles' => [
                        'form_options' => [
                            'entry_options' => [
                                'required' => false
                            ]
                        ]
                    ]
                ]
            ]
        );

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name'     => 'Updated Magazine 1',
                    'articles' => [
                        [
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ],
                        null
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'Updated Magazine 1',
                        'articles'    => [
                            [
                                'id'       => '@article1->id',
                                'headline' => 'Updated Article 1',
                                'body'     => 'Updated Article 1 Body'
                            ]
                        ],
                        'bestArticle' => [
                            'id'       => '@article1->id',
                            'headline' => 'Updated Article 1',
                            'body'     => 'Updated Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 2');
        $this->assertArticleExists('Article 3');
    }

    public function testCreateForToOneAssociation()
    {
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'attributes' => [
                    'name'        => 'New Magazine 1',
                    'bestArticle' => [
                        'headline' => 'New Article 1',
                        'body'     => 'New Article 1 Body'
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazines'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);
        $article1Id = $this->getArticleId('New Article 1');

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'New Magazine 1',
                        'articles'    => [],
                        'bestArticle' => [
                            'id'       => $article1Id,
                            'headline' => 'New Article 1',
                            'body'     => 'New Article 1 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateForToManyAssociation()
    {
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'attributes' => [
                    'name'     => 'New Magazine 1',
                    'articles' => [
                        [
                            'headline' => 'New Article 1',
                            'body'     => 'New Article 1 Body'
                        ],
                        [
                            'headline' => 'New Article 2',
                            'body'     => 'New Article 2 Body'
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazines'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);
        $article1Id = $this->getArticleId('New Article 1');
        $article2Id = $this->getArticleId('New Article 2');

        $response = $this->get(
            ['entity' => 'testapimagazines', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazines',
                    'id'         => (string)$magazineId,
                    'attributes' => [
                        'name'        => 'New Magazine 1',
                        'articles'    => [
                            [
                                'id'       => $article1Id,
                                'headline' => 'New Article 1',
                                'body'     => 'New Article 1 Body'
                            ],
                            [
                                'id'       => $article2Id,
                                'headline' => 'New Article 2',
                                'body'     => 'New Article 2 Body'
                            ]
                        ],
                        'bestArticle' => null
                    ]
                ]
            ],
            $response
        );
    }
}

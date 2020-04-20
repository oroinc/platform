<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestArticle;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class ModelWithAssociationsAsAttributesTest extends RestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/test_magazine.yml']);
    }

    /**
     * @param string $headline
     *
     * @return int
     */
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

    /**
     * @param string $headline
     */
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
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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
                'type'       => 'testapimagazinemodel2',
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
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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

    public function testUpdateForToOneAssociationWhenPreviousValueIsNull()
    {
        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazinemodel2',
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
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId],
            $data
        );
        $newArticleId = $this->getArticleId('Updated New Article');

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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

    public function testUpdateForToOneAssociationWhenNewValueIsNull()
    {
        self::markTestSkipped('Need to find a way to set null');
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazinemodel2',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'bestArticle' => null
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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

    public function testUpdateForToManyAssociation()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazinemodel2',
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
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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

    public function testUpdateForToManyAssociationWhenPreviousValueIsEmpty()
    {
        $magazineId = $this->getReference('magazine2')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazinemodel2',
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
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId],
            $data
        );
        $newArticle1Id = $this->getArticleId('Updated New Article 1');
        $newArticle2Id = $this->getArticleId('Updated New Article 2');

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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
                'type'       => 'testapimagazinemodel2',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => []
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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

    public function testCreateForToOneAssociation()
    {
        $data = [
            'data' => [
                'type'       => 'testapimagazinemodel2',
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
            ['entity' => 'testapimagazinemodel2'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);
        $article1Id = $this->getArticleId('New Article 1');

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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
                'type'       => 'testapimagazinemodel2',
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
            ['entity' => 'testapimagazinemodel2'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);
        $article1Id = $this->getArticleId('New Article 1');
        $article2Id = $this->getArticleId('New Article 2');

        $response = $this->get(
            ['entity' => 'testapimagazinemodel2', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapimagazinemodel2',
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

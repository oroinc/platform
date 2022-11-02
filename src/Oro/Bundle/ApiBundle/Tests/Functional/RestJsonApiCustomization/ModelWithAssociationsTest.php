<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestArticle;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class ModelWithAssociationsTest extends RestJsonApiTestCase
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

    public function testGet()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $response = $this->get(
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId],
            ['include' => 'articles,bestArticle']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapimagazinemodel1',
                    'id'            => (string)$magazineId,
                    'attributes'    => [
                        'name' => 'Magazine 1'
                    ],
                    'relationships' => [
                        'articles'    => [
                            'data' => [
                                ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article1->id)>'],
                                ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article2->id)>'],
                                ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article3->id)>']
                            ]
                        ],
                        'bestArticle' => [
                            'data' => ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article1->id)>']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapiarticlemodel1',
                        'id'         => '<toString(@article1->id)>',
                        'attributes' => [
                            'headline' => 'Article 1',
                            'body'     => 'Article 1 Body'
                        ]
                    ],
                    [
                        'type'       => 'testapiarticlemodel1',
                        'id'         => '<toString(@article2->id)>',
                        'attributes' => [
                            'headline' => 'Article 2',
                            'body'     => 'Article 2 Body'
                        ]
                    ],
                    [
                        'type'       => 'testapiarticlemodel1',
                        'id'         => '<toString(@article3->id)>',
                        'attributes' => [
                            'headline' => 'Article 3',
                            'body'     => 'Article 3 Body'
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
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId],
            ['include' => 'articles,bestArticle']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapimagazinemodel1',
                    'id'            => (string)$magazineId,
                    'attributes'    => [
                        'name' => 'Magazine 2'
                    ],
                    'relationships' => [
                        'articles'    => ['data' => []],
                        'bestArticle' => ['data' => null]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateWithoutAssociations()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazinemodel1',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'name' => 'Updated Magazine 1'
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId],
            $data
        );

        $response = $this->get(
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapimagazinemodel1',
                    'id'            => (string)$magazineId,
                    'attributes'    => [
                        'name' => 'Updated Magazine 1'
                    ],
                    'relationships' => [
                        'articles'    => [
                            'data' => [
                                ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article1->id)>'],
                                ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article2->id)>'],
                                ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article3->id)>']
                            ]
                        ],
                        'bestArticle' => [
                            'data' => ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateToOneAssociation()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data'     => [
                'type'          => 'testapimagazinemodel1',
                'id'            => (string)$magazineId,
                'attributes'    => [
                    'name' => 'Updated Magazine 1'
                ],
                'relationships' => [
                    'bestArticle' => [
                        'data' => ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article1->id)>']
                    ]
                ]
            ],
            'included' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'testapiarticlemodel1',
                    'id'         => '<toString(@article1->id)>',
                    'attributes' => [
                        'headline' => 'Updated Article 1',
                        'body'     => 'Updated Article 1 Body'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'Only manageable entity can be updated.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testTryToUpdateToManyAssociation()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data'     => [
                'type'          => 'testapimagazinemodel1',
                'id'            => (string)$magazineId,
                'attributes'    => [
                    'name' => 'Updated Magazine 1'
                ],
                'relationships' => [
                    'articles' => [
                        'data' => [
                            ['type' => 'testapiarticlemodel1', 'id' => '<toString(@article1->id)>']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'testapiarticlemodel1',
                    'id'         => '<toString(@article1->id)>',
                    'attributes' => [
                        'headline' => 'Updated Article 1',
                        'body'     => 'Updated Article 1 Body'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'Only manageable entity can be updated.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testCreateForToOneAssociation()
    {
        $data = [
            'data'     => [
                'type'          => 'testapimagazinemodel1',
                'attributes'    => [
                    'name' => 'New Magazine 1'
                ],
                'relationships' => [
                    'bestArticle' => [
                        'data' => ['type' => 'testapiarticlemodel1', 'id' => 'new_article1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'testapiarticlemodel1',
                    'id'         => 'new_article1',
                    'attributes' => [
                        'headline' => 'New Article 1',
                        'body'     => 'New Article 1 Body'
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazinemodel1'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);
        $article1Id = $this->getArticleId('New Article 1');

        $response = $this->get(
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId],
            ['include' => 'articles,bestArticle']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapimagazinemodel1',
                    'id'            => (string)$magazineId,
                    'attributes'    => [
                        'name' => 'New Magazine 1'
                    ],
                    'relationships' => [
                        'articles'    => ['data' => []],
                        'bestArticle' => ['data' => ['type' => 'testapiarticlemodel1', 'id' => (string)$article1Id]]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapiarticlemodel1',
                        'id'         => (string)$article1Id,
                        'attributes' => [
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
            'data'     => [
                'type'          => 'testapimagazinemodel1',
                'attributes'    => [
                    'name' => 'New Magazine 1'
                ],
                'relationships' => [
                    'articles' => [
                        'data' => [
                            ['type' => 'testapiarticlemodel1', 'id' => 'new_article1'],
                            ['type' => 'testapiarticlemodel1', 'id' => 'new_article2']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'testapiarticlemodel1',
                    'id'         => 'new_article1',
                    'attributes' => [
                        'headline' => 'New Article 1',
                        'body'     => 'New Article 1 Body'
                    ]
                ],
                [
                    'type'       => 'testapiarticlemodel1',
                    'id'         => 'new_article2',
                    'attributes' => [
                        'headline' => 'New Article 2',
                        'body'     => 'New Article 2 Body'
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazinemodel1'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);
        $article1Id = $this->getArticleId('New Article 1');
        $article2Id = $this->getArticleId('New Article 2');

        $response = $this->get(
            ['entity' => 'testapimagazinemodel1', 'id' => (string)$magazineId],
            ['include' => 'articles,bestArticle']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapimagazinemodel1',
                    'id'            => (string)$magazineId,
                    'attributes'    => [
                        'name' => 'New Magazine 1'
                    ],
                    'relationships' => [
                        'articles'    => [
                            'data' => [
                                ['type' => 'testapiarticlemodel1', 'id' => (string)$article1Id],
                                ['type' => 'testapiarticlemodel1', 'id' => (string)$article2Id]
                            ]
                        ],
                        'bestArticle' => ['data' => null]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapiarticlemodel1',
                        'id'         => (string)$article1Id,
                        'attributes' => [
                            'headline' => 'New Article 1',
                            'body'     => 'New Article 1 Body'
                        ]
                    ],
                    [
                        'type'       => 'testapiarticlemodel1',
                        'id'         => (string)$article2Id,
                        'attributes' => [
                            'headline' => 'New Article 2',
                            'body'     => 'New Article 2 Body'
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}

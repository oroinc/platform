<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDataTypes;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestArticle;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestMagazine;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityWithCollapsedAssociationsAsAttributes extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/test_magazine.yml']);
        $this->appendEntityConfig(TestMagazine::class, $this->getEntityConfig());
    }

    private function getEntityConfig(): array
    {
        return [
            'fields' => [
                'articles'    => [
                    'data_type'        => 'array',
                    'exclusion_policy' => 'all',
                    'collapse'         => true,
                    'fields'           => [
                        'headline' => [
                            'form_options' => [
                                'constraints' => [['NotBlank' => []]]
                            ]
                        ]
                    ]
                ],
                'bestArticle' => [
                    'data_type'        => 'scalar',
                    'exclusion_policy' => 'all',
                    'collapse'         => true,
                    'fields'           => [
                        'headline' => [
                            'form_options' => [
                                'constraints' => [['NotBlank' => []]]
                            ]
                        ]
                    ]
                ]
            ]
        ];
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
                        'articles'    => ['Article 1', 'Article 2', 'Article 3'],
                        'bestArticle' => 'Article 1'
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
                    'bestArticle' => 'Updated Article 1'
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
                        'articles'    => ['Updated Article 1', 'Article 2', 'Article 3'],
                        'bestArticle' => 'Updated Article 1'
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
                    'bestArticle' => 'Updated New Article'
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
                        'bestArticle' => 'Updated New Article'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateForToOneAssociationWhenNewValueIsNull()
    {
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
                'source' => ['pointer' => '/data/attributes/bestArticle']
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenNewValueIsNullAndRequiredIsFalse()
    {
        $entityConfig = $this->getEntityConfig();
        $entityConfig['fields']['bestArticle']['form_options'] = [
            'required' => false
        ];
        $this->appendEntityConfig(TestMagazine::class, $entityConfig);

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
                        'articles'    => ['Article 1', 'Article 2', 'Article 3'],
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
                'source' => ['pointer' => '/data/attributes/bestArticle']
            ],
            $response
        );
    }

    public function testUpdateForToOneAssociationWhenNewValueIsNullAndPreviousValueIsNullAndRequiredIsFalse()
    {
        $entityConfig = $this->getEntityConfig();
        $entityConfig['fields']['bestArticle']['form_options'] = [
            'required' => false
        ];
        $this->appendEntityConfig(TestMagazine::class, $entityConfig);

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
        $this->assertArticleExists('Article 1');
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
                    'articles' => ['Updated Article 1', 'Updated Article 2']
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
                        'articles'    => ['Updated Article 1', 'Updated Article 2'],
                        'bestArticle' => 'Updated Article 1'
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
                    'articles' => ['Updated New Article 1', 'Updated New Article 2']
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
                        'articles'    => ['Updated New Article 1', 'Updated New Article 2'],
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
                        'bestArticle' => 'Article 1'
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 1');
        $this->assertArticleExists('Article 2');
        $this->assertArticleExists('Article 3');
    }

    public function testTryToUpdateForToManyAssociationWhenFirstItemIsNull()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => [null, 'Updated Article 2']
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
                'source' => ['pointer' => '/data/attributes/articles/0']
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociationWhenFirstItemIsNullAndRequiredIsFalse()
    {
        $entityConfig = $this->getEntityConfig();
        $entityConfig['fields']['articles']['form_options'] = [
            'entry_options' => [
                'required' => false
            ]
        ];
        $this->appendEntityConfig(TestMagazine::class, $entityConfig);

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => [null, 'Updated Article 2']
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
                        'articles'    => ['Updated Article 2'],
                        'bestArticle' => 'Article 1'
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
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => ['Updated Article 1', null, 'Updated Article 3']
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
                'source' => ['pointer' => '/data/attributes/articles/1']
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociationWhenMiddleItemIsNullAndRequiredIsFalse()
    {
        $entityConfig = $this->getEntityConfig();
        $entityConfig['fields']['articles']['form_options'] = [
            'entry_options' => [
                'required' => false
            ]
        ];
        $this->appendEntityConfig(TestMagazine::class, $entityConfig);

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => ['Updated Article 1', null, 'Updated Article 3']
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
                        'articles'    => ['Updated Article 1', 'Updated Article 3'],
                        'bestArticle' => 'Updated Article 1'
                    ]
                ]
            ],
            $response
        );
        $this->assertArticleExists('Article 2');
    }

    public function testTryToUpdateForToManyAssociationWhenLastItemIsNull()
    {
        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => ['Updated Article 1', null]
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
                'source' => ['pointer' => '/data/attributes/articles/1']
            ],
            $response
        );
    }

    public function testUpdateForToManyAssociationWhenLastItemIsNullAndRequiredIsFalse()
    {
        $entityConfig = $this->getEntityConfig();
        $entityConfig['fields']['articles']['form_options'] = [
            'entry_options' => [
                'required' => false
            ]
        ];
        $this->appendEntityConfig(TestMagazine::class, $entityConfig);

        $magazineId = $this->getReference('magazine1')->getId();
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'id'         => (string)$magazineId,
                'attributes' => [
                    'articles' => ['Updated Article 1', null]
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
                        'articles'    => ['Updated Article 1'],
                        'bestArticle' => 'Updated Article 1'
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
                    'bestArticle' => 'New Article 1'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazines'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);

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
                        'bestArticle' => 'New Article 1'
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
                    'articles' => ['New Article 1', 'New Article 2']
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazines'],
            $data
        );
        $magazineId = (int)$this->getResourceId($response);

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
                        'articles'    => ['New Article 1', 'New Article 2'],
                        'bestArticle' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateForToOneAssociationWithValidationError()
    {
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'attributes' => [
                    'name'        => 'New Magazine 1',
                    'bestArticle' => ''
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazines'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/bestArticle']
            ],
            $response
        );
    }

    public function testTryToCreateForToManyAssociationWithValidationError()
    {
        $data = [
            'data' => [
                'type'       => 'testapimagazines',
                'attributes' => [
                    'name'     => 'New Magazine 1',
                    'articles' => ['New Article 1', '']
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testapimagazines'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/articles/1']
            ],
            $response
        );
    }
}

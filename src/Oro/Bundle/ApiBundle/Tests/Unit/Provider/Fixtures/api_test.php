<?php

// @codingStandardsIgnoreStart
return array(
    'config' => array(
        'api_test.yml' => array(
            'entities' => array(
                'Oro\\Bundle\\ApiBundle\\Tests\\Unit\\Fixtures\\Entity\\User' => array(
                    'fields' => array(
                        'groups' => array(
                            'exclude' => true,
                        ),
                    ),
                ),
            ),
            'relations' => array(),
        ),
    ),
    'aliases' => array(
        'Oro\\Bundle\\ApiBundle\\Tests\\Unit\\Fixtures\\Entity\\User' => array(
            'alias' => 'user',
            'plural_alias' => 'users',
        ),
    ),
    'substitutions' => array(
        'Oro\\Bundle\\ApiBundle\\Tests\\Unit\\Fixtures\\Entity\\User' => 'Oro\\Bundle\\ApiBundle\\Tests\\Unit\\Fixtures\\Entity\\UserProfile',
    ),
    'excluded_entities' => array(
        0 => 'Oro\\Bundle\\ApiBundle\\Tests\\Unit\\Fixtures\\Entity\\User',
    ),
    'exclusions' => array(
        0 => array(
            'entity' => 'Oro\\Bundle\\ApiBundle\\Tests\\Unit\\Fixtures\\Entity\\User',
            'field' => 'name',
        ),
    ),
    'inclusions' => array(
        0 => array(
            'entity' => 'Oro\\Bundle\\ApiBundle\\Tests\\Unit\\Fixtures\\Entity\\User',
            'field' => 'category',
        ),
    ),
);

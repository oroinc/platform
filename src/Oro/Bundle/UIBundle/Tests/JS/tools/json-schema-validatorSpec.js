import jsonSchemaValidator from 'oroui/js/tools/json-schema-validator';

describe('oroui/js/tools/json-schema-validator', () => {
    beforeEach(() => {
        jasmine.addMatchers({
            toBeValidJSON(matchersUtil) {
                return {
                    compare(actual, expected) {
                        const data = actual;
                        const schema = expected;
                        const result = {
                            pass: jsonSchemaValidator.validate(schema, data)
                        };
                        result.message = 'Expected ' + matchersUtil.pp(data) + (result.pass ? ' not' : '') +
                            ' to be valid against ' + matchersUtil.pp(schema);
                        return result;
                    }
                };
            }
        });
    });

    const testSuite = {
        'string': {
            schema: {type: 'string'},
            valid: [
                'This is a string',
                'Déjà vu',
                '',
                '42'
            ],
            invalid: [42]
        },
        'string length limit': {
            schema: {
                type: 'string',
                minLength: 2,
                maxLength: 3
            },
            valid: ['AB', 'ABC'],
            invalid: ['A', 'ABCD']
        },
        'string with pattern': {
            schema: {
                type: 'string',
                pattern: '^(\\([0-9]{3}\\))?[0-9]{3}-[0-9]{4}$'
            },
            valid: [
                '555-1212',
                '(888)555-1212'
            ],
            invalid: [
                '(888)555-1212 ext. 532',
                '(800)FLOWERS'
            ]
        },
        'integer number': {
            schema: {
                type: 'integer'
            },
            valid: [42, -1],
            invalid: [3.1415926, '42']
        },
        'number': {
            schema: {
                type: 'number'
            },
            valid: [42, -1, 5.0, 2.99792458e8],
            invalid: ['42']
        },
        'number range': {
            schema: {
                type: 'number',
                minimum: 0,
                maximum: 100,
                exclusiveMaximum: true
            },
            valid: [0, 10, 99],
            invalid: [-1, 100, 101]
        },
        'object': {
            schema: {
                type: 'object'
            },
            valid: [
                {
                    key: 'value',
                    another_key: 'another_value'
                },
                {
                    Sun: 1.9891e30,
                    Jupiter: 1.8986e27,
                    Saturn: 5.6846e26,
                    Neptune: 10.243e25,
                    Uranus: 8.6810e25,
                    Earth: 5.9736e24,
                    Venus: 4.8685e24,
                    Mars: 6.4185e23,
                    Mercury: 3.3022e23,
                    Moon: 7.349e22,
                    Pluto: 1.25e22
                }
            ],
            invalid: [
                'Not an object',
                ['An', 'array', 'not', 'an', 'object']
            ]
        },
        'object with properties structure': {
            schema: {
                type: 'object',
                properties: {
                    number: {type: 'number'},
                    street_name: {type: 'string'},
                    street_type: {
                        'type': 'string',
                        'enum': ['Street', 'Avenue', 'Boulevard']
                    }
                }
            },
            valid: [
                {number: 1600, street_name: 'Pennsylvania', street_type: 'Avenue'},
                {number: 1600, street_name: 'Pennsylvania'},
                {},
                {number: 1600, street_name: 'Pennsylvania', street_type: 'Avenue', direction: 'NW'}

            ],
            invalid: [
                {number: '1600', street_name: 'Pennsylvania', street_type: 'Avenue'}
            ]
        },
        'object with no additional properties': {
            schema: {
                type: 'object',
                properties: {
                    number: {type: 'number'},
                    street_name: {type: 'string'},
                    street_type: {
                        'type': 'string',
                        'enum': ['Street', 'Avenue', 'Boulevard']
                    }
                },
                additionalProperties: false
            },
            valid: [
                {number: 1600, street_name: 'Pennsylvania', street_type: 'Avenue'}
            ],
            invalid: [
                {number: 1600, street_name: 'Pennsylvania', street_type: 'Avenue', direction: 'NW'}
            ]
        },
        'object with additional properties structure': {
            schema: {
                type: 'object',
                properties: {
                    number: {type: 'number'},
                    street_name: {type: 'string'},
                    street_type: {
                        'type': 'string',
                        'enum': ['Street', 'Avenue', 'Boulevard']
                    }
                },
                additionalProperties: {type: 'string'}
            },
            valid: [
                {number: 1600, street_name: 'Pennsylvania', street_type: 'Avenue'},
                {number: 1600, street_name: 'Pennsylvania', street_type: 'Avenue', direction: 'NW'}
            ],
            invalid: [
                {number: 1600, street_name: 'Pennsylvania', street_type: 'Avenue', office_number: 201}
            ]
        },
        'object with required properties': {
            schema: {
                type: 'object',
                properties: {
                    name: {type: 'string'},
                    email: {type: 'string'},
                    address: {type: 'string'},
                    telephone: {type: 'string'}
                },
                required: ['name', 'email']
            },
            valid: [
                {
                    name: 'William Shakespeare',
                    email: 'bill@stratford-upon-avon.co.uk'
                },
                {
                    name: 'William Shakespeare',
                    email: 'bill@stratford-upon-avon.co.uk',
                    address: 'Henley Street, Stratford-upon-Avon, Warwickshire, England',
                    authorship: 'in question'
                }
            ],
            invalid: [
                {
                    name: 'William Shakespeare',
                    address: 'Henley Street, Stratford-upon-Avon, Warwickshire, England'
                }
            ]
        },
        'object with properties size': {
            schema: {
                type: 'object',
                minProperties: 2,
                maxProperties: 3
            },
            valid: [
                {a: 0, b: 1},
                {a: 0, b: 1, c: 2}
            ],
            invalid: [{}, {a: 0}, {a: 0, b: 1, c: 2, d: 3}]
        },
        'array': {
            schema: {type: 'array'},
            valid: [
                [1, 2, 3, 4, 5],
                [3, 'different', {types: 'of values'}]
            ],
            invalid: [
                {Not: 'an array'}
            ]
        },
        'typed array': {
            schema: {
                type: 'array',
                items: {type: 'number'}
            },
            valid: [
                [1, 2, 3, 4, 5],
                []
            ],
            invalid: [
                [1, 2, '3', 4, 5]
            ]
        },
        'tuple': {
            schema: {
                type: 'array',
                items: [
                    {type: 'number'},
                    {type: 'string'},
                    {'type': 'string', 'enum': ['Street', 'Avenue', 'Boulevard']},
                    {'type': 'string', 'enum': ['NW', 'NE', 'SW', 'SE']}
                ]
            },
            valid: [
                [1600, 'Pennsylvania', 'Avenue', 'NW'],
                [10, 'Downing', 'Street'],
                [1600, 'Pennsylvania', 'Avenue', 'NW', 'Washington']
            ],
            invalid: [
                [24, 'Sussex', 'Drive'],
                ['Palais de l\'Élysée']
            ]
        },
        'tuple with no additional items': {
            schema: {
                type: 'array',
                items: [
                    {type: 'number'},
                    {type: 'string'},
                    {'type': 'string', 'enum': ['Street', 'Avenue', 'Boulevard']},
                    {'type': 'string', 'enum': ['NW', 'NE', 'SW', 'SE']}
                ],
                additionalItems: false
            },
            valid: [
                [1600, 'Pennsylvania', 'Avenue', 'NW'],
                [1600, 'Pennsylvania', 'Avenue']

            ],
            invalid: [
                [1600, 'Pennsylvania', 'Avenue', 'NW', 'Washington']
            ]
        },
        'array with limited length': {
            schema: {
                type: 'array',
                minItems: 2,
                maxItems: 3
            },
            valid: [
                [1, 2],
                [1, 2, 3]
            ],
            invalid: [
                [],
                [1],
                [1, 2, 3, 4]
            ]
        },
        'array with unique items': {
            schema: {
                type: 'array',
                uniqueItems: true
            },
            valid: [
                [1, 2, 3, 4, 5],
                []
            ],
            invalid: [
                [1, 2, 3, 3, 4]
            ]
        },
        'boolean': {
            schema: {type: 'boolean'},
            valid: [true, false],
            invalid: ['true', 0]
        },
        'null': {
            schema: {type: 'null'},
            valid: [null],
            invalid: [false, 0, '']
        },
        'strings enum': {
            schema: {
                'type': 'string',
                'enum': ['red', 'amber', 'green']
            },
            valid: ['red'],
            invalid: ['blue']
        },
        'untyped enum': {
            schema: {
                'enum': ['red', 'amber', 'green', null, 42]
            },
            valid: ['red', null, 42],
            invalid: [0]
        },
        'enum with type priority': {
            schema: {
                'type': 'string',
                'enum': ['red', 'amber', 'green', null]
            },
            valid: ['red'],
            invalid: [null]
        },
        'list of types': {
            schema: {
                type: ['integer', 'string']
            },
            valid: [42, -1, '42', ''],
            invalid: [3.1415926, null]
        }
    };

    Object.keys(testSuite).forEach(caseName => {
        it(caseName, () => {
            const schema = testSuite[caseName].schema;

            testSuite[caseName].valid.forEach(data => {
                expect(data).toBeValidJSON(schema);
            });

            testSuite[caseName].invalid.forEach(data => {
                expect(data).not.toBeValidJSON(schema);
            });
        });
    });
});

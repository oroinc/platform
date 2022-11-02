define([
    'underscore'
], function(_) {
    'use strict';

    describe('underscore', function() {
        it('should render inner template', function() {
            const innerTemplate = '<p><%#' +
                '<span>Listed price: <%= listedPrice %></span>' +
                '#%> %></p>';
            const template = _.template(innerTemplate);
            const result = template({listedPrice: 100});

            expect(result).toEqual('<p><span>Listed price: &lt;%= listedPrice %&gt;</span> %></p>');
        });

        it('check include inner template ', function() {
            const innerTemplate = '<span><%= price %></span>';
            const escapeInnerTemplate =
                '<div>' +
                '<p><%= label %>' +
                '<%#' + innerTemplate + ' #%>' +
                '</p>' +
                '</div>';

            const basicTemplate = _.template(escapeInnerTemplate);
            const result = basicTemplate({label: 'Listed Price:', price: 100});

            expect(result).toEqual('<div><p>Listed Price:<span>&lt;%= price %&gt;</span> </p></div>');
        });

        it('should use custom tags for inner template', function() {
            const innerTemplate = '{{# ' +
                '<span>Listed price: <%= listedPrice %></span>' +
                ' #}}';
            const template = _.template(innerTemplate, {innerTempStart: '{{#', innerTempEnd: '#}}'});
            const result = template({listedPrice: 100});

            expect(result).toEqual('<span>Listed price: 100</span>');
        });

        describe('escapeForJSON should return safe string for JSON definition over strings concatenation', function() {
            const cases = [
                ['string', '"\/\b\f\t\n\r\\'],
                ['number', 3.45],
                ['null', null],
                ['boolean', true]
            ];

            cases.forEach(testCase => {
                it(`escape "${testCase[0]}" type value for JSON`, function() {
                    const value = testCase[1];
                    const jsonString = '{"prop": "prefix ' + _.escapeForJSON(value) + ' suffix"}';
                    const obj = JSON.parse(jsonString);
                    expect(obj.prop).toEqual('prefix ' + value + ' suffix');
                });
            });
        });
    });
});

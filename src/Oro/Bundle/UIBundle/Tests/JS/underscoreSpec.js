define([
    'underscore'
], function(_) {
    'use strict';

    describe('underscore', function() {
        it('should render inner template', function() {
            var innerTemplate = '<p><%#' +
                '<span>Listed price: <%= listedPrice %></span>' +
                '#%> %></p>';
            var template = _.template(innerTemplate);
            var result = template({listedPrice: 100});

            expect(result).toEqual('<p><span>Listed price: &lt;%= listedPrice %&gt;</span> %></p>');
        });

        it('check include inner template ', function() {
            var innerTemplate = '<span><%= price %></span>';
            var escapeInnerTemplate =
                '<div>' +
                '<p><%= label %>' +
                '<%#' + innerTemplate + ' #%>' +
                '</p>' +
                '</div>';

            var basicTemplate = _.template(escapeInnerTemplate);
            var result = basicTemplate({label: 'Listed Price:', price: 100});

            expect(result).toEqual('<div><p>Listed Price:<span>&lt;%= price %&gt;</span> </p></div>');
        });

        it('should use custom tags for inner template', function() {
            _.templateSettings.innerTempStart = '{{#';
            _.templateSettings.innerTempEnd = '#}}';

            var innerTemplate = '{{# ' +
                '<span>Listed price: <%= listedPrice %></span>' +
                ' #}}';
            var template = _.template(innerTemplate);
            var result = template({listedPrice: 100});

            expect(result).toEqual('<span>Listed price: 100</span>');
        });
    });
});

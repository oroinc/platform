define(function() {
    'use strict';

    return {
        text2html: function(content) {
            // keep paragraphs at least
            return '<p>' + String(content).replace(/(\n\r?|\r\n?)/g, '</p><p>') + '</p>';
        },
        html2text: function(content) {
            return String(content)
                .replace(/<head>[^]*<\/head>/, '')
                .replace(/(<\/?[^>]+>|&[^;]+;)/g, '')
                .replace(/\s*\n{2,}/g, '\n\n')
                .trim();
        }
    };
});

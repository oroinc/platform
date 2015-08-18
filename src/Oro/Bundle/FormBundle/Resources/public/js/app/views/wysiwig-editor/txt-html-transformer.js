define(function() {
    'use strict';

    return {
        text2html: function(content) {
            // keep paragraphs at least
            return '<p>' + String(content).replace(/(\n\r?|\r\n?)/g, '</p><p>') + '</p>';
        },
        html2text: function(content) {
            return String(content).replace(/(<\/?[^>]+>|&[^;]+;)/g, '');
        },
        html2multiline: function(content) {
            var text = this.html2text(String(content).replace(/(<\/(div|p)>)/g, '\n\r'));
            var lines = text.split('\n\r').map(function(line) {
                return line.trim();
            });
            return _.filter(lines, function(line) {
                return line.length;
            });
        }
    };
});

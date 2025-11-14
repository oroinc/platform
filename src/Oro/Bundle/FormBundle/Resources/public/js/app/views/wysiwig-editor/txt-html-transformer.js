export default {
    html2text: function(content) {
        return String(content)
            .replace(/<head>[^]*<\/head>/, '')
            .replace(/<\/?[^>]+>/g, '')
            .replace(/\s*\n{2,}/g, '\n\n')
            .trim();
    }
};

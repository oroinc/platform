import loadModules from 'oroui/js/app/services/load-modules';

export default function(options) {
    const {tags} = options;
    loadModules('orosync/js/content-manager')
        .then(contentManager => contentManager.tagContent(tags));
};

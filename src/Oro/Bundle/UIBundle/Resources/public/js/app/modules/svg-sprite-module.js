import {getThemeSpriteMetadata} from 'underscore';
import getConfig from 'module-config';
import mediator from 'oroui/js/mediator';

const config = getConfig(module.id);
const themeSpriteUrl = getThemeSpriteMetadata();

if (config.debug && themeSpriteUrl) {
    fetch(themeSpriteUrl)
        .then(response => response.json())
        .then(({icons}) => {
            const highlightIcons = availableIcons => {
                const svgElements = document.querySelectorAll('.theme-icon');
                for (const svgEl of svgElements) {
                    const svgUseEl = svgEl.querySelector('use');

                    if (svgUseEl) {
                        const [, svgId] = svgUseEl.getAttribute('href').split('#');
                        if (!availableIcons[svgId]) {
                            svgEl.classList.add('not-found');
                        }
                    }
                }
            };

            highlightIcons(icons);

            mediator.on('layout:init', () => highlightIcons(icons));
            mediator.on('layout:reposition', () => highlightIcons(icons));
            mediator.on('widget_dialog:open', () => highlightIcons(icons));
            document.addEventListener('content:changed', e => highlightIcons(icons));
        })
        .catch(error => {
            console.warn(error);
        });
}

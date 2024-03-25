import {showTooltip} from '@codemirror/view';
import {StateField} from '@codemirror/state';

export default function elementTooltip({util, dataSource, getDataSourceCallback}) {
    const getElementTooltips = state => {
        const content = util.unNormalizePropertyNamesExpression(state.doc.toString());

        if (typeof getDataSourceCallback !== 'function') {
            return [];
        }

        return state.selection.ranges
            .map(range => {
                return {
                    ...util.getAutocompleteData(content, range.to),
                    range
                };
            })
            .filter(({dataSourceKey, itemsType}) => {
                return itemsType === 'datasource' && dataSourceKey && (dataSourceKey in dataSource);
            })
            .map(({range, dataSourceKey, dataSourceValue}) => {
                return {
                    pos: range.head,
                    above: true,
                    arrow: false,
                    create: () => {
                        const tooltipContent = getDataSourceCallback(dataSourceKey, dataSourceValue);
                        const dom = document.createElement('div');

                        if (tooltipContent) {
                            tooltipContent.$widget.appendTo(dom);
                            setTimeout(() => {
                                tooltipContent.$widget.trigger('content:changed');
                            });
                        }

                        dom.className = 'cm-tooltip-control';
                        return {dom};
                    }
                };
            });
    };

    return [StateField.define({
        create: getElementTooltips,

        update(tooltips, transaction) {
            if ((!transaction.docChanged && !transaction.selection)) {
                return tooltips;
            }

            return getElementTooltips(transaction.state);
        },

        provide: f => showTooltip.computeN([f], state => state.field(f))
    })];
}

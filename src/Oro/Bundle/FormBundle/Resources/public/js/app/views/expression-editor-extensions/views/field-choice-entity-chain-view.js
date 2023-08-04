import __ from 'orotranslation/js/translator';
import FieldChoiceView from 'oroentity/js/app/views/field-choice-view';

const FieldChoiceEntityChainView = FieldChoiceView.extend({
    optionNames: ['supportedNames', 'entityDataProvider', 'handler', 'dataSourceNames'],

    rootSelected: false,

    constructor: function FieldChoiceEntityChainView(...args) {
        FieldChoiceEntityChainView.__super__.constructor.apply(this, args);
    },

    onChange(event) {
        const selectedItem = event.added || this.getData();

        if (selectedItem) {
            this.handler(this.convertPathToEditorStringChain(selectedItem.id));
            this.setValue('');

            this.isMultiplyEntity() && this.setEntity();
        }
    },

    convertPathToEditorStringChain(path) {
        return this.dataProvider.pathToEntityChain(path).map(({entity, field}) => {
            if (field) {
                return field.name;
            }

            if (entity) {
                if (this.dataSourceNames.indexOf(entity.alias) !== -1) {
                    return `${entity.alias}[#{1}]`;
                }

                return entity.alias;
            }
        }).join('.');
    },

    setEntity(path = null) {
        this.entity = path;

        if (this.dataProvider) {
            this.dataProvider.setRootEntityClassName(path);
        }
    },

    isMultiplyEntity() {
        return !!this.supportedNames?.length;
    },

    _select2Data(path) {
        if (this.isMultiplyEntity()) {
            if (!this.entity && path) {
                this.setEntity(path);
            }

            if (this.entity && !path) {
                this.setEntity();
            }

            if (this.supportedNames?.length && !this.entity) {
                return [{
                    text: __('oro.form.expression_editor.fields.entity'),
                    children: this.supportedNames.map(entityName => {
                        const fields = this.dataProvider.getEntityTreeNodeByPropertyPath(entityName);

                        return {
                            pagePath: fields.__entity.className.replace(/[\\]+/g, '\\'),
                            text: fields.__entity.label
                        };
                    })
                }];
            }
        }

        if (path.indexOf('+') === -1) {
            path = '';
        }

        return FieldChoiceEntityChainView.__super__._select2Data.call(this, path);
    },

    _prepareSelect2Options(options) {
        const select2Opts = FieldChoiceEntityChainView.__super__._prepareSelect2Options.call(this, options);

        select2Opts.breadcrumbs = pagePath => {
            let chain = [];

            if (pagePath && pagePath.indexOf('+') !== -1) {
                chain = this.dataProvider.pathToEntityChainExcludeTrailingFieldSafely(pagePath);
            } else {
                chain = this.dataProvider.pathToEntityChain();
            }

            chain.forEach(item => {
                item.pagePath = item.basePath;
            });

            if (!chain.length) {
                chain = [{
                    field: {
                        label: `<span class="select2-breadcrumb-placeholder">
                            ${__('oro.form.expression_editor.fields.breadcrumbs_placeholder')}
                        </span>`
                    }
                }];
            }

            return chain;
        };

        return select2Opts;
    }
});

export default FieldChoiceEntityChainView;

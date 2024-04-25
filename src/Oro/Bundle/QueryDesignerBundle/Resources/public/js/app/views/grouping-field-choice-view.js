import FieldChoiceView from 'oroentity/js/app/views/field-choice-view';

const GroupingFieldChoiceView = FieldChoiceView.extend({
    constructor: function GroupingFieldChoiceView(...args) {
        GroupingFieldChoiceView.__super__.constructor.apply(this, args);
    },

    setDynamicCollection(collection) {
        this.dynamicEntityFieldsCollection = collection;
    },

    _select2Data(path) {
        return this.addFuncFieldsToSelect(GroupingFieldChoiceView.__super__._select2Data.call(this, path));
    },

    addFuncFieldsToSelect(data) {
        const insertAfterParent = (items, field) => {
            for (const [index, item] of items.entries()) {
                if (item.children && item.children.length) {
                    insertAfterParent(item.children, field);
                }

                if (item.id && item.id === field.name) {
                    items.splice(index, 0, {
                        id: field.id,
                        text: field.text
                    });
                    break;
                }
            }
        };

        if (this.dynamicEntityFieldsCollection) {
            this.dynamicEntityFieldsCollection.getSelectData().forEach(field => insertAfterParent(data, field));
        }

        return data;
    },

    _prepareSelect2Options(options) {
        const select2Opts = GroupingFieldChoiceView.__super__._prepareSelect2Options.call(this, options);

        const parentBreadcrumbs = select2Opts.breadcrumbs;
        select2Opts.breadcrumbs = pagePath => {
            return parentBreadcrumbs(pagePath ? this.dynamicEntityFieldsCollection.extractName(pagePath) : pagePath);
        };

        return select2Opts;
    },

    pathCallback(path) {
        return this.dynamicEntityFieldsCollection.extractName(path);
    },

    setEntity(...args) {
        if (this.dynamicEntityFieldsCollection) {
            this.dynamicEntityFieldsCollection.reset();
        }

        GroupingFieldChoiceView.__super__.setEntity.apply(this, args);
    }
});

export default GroupingFieldChoiceView;

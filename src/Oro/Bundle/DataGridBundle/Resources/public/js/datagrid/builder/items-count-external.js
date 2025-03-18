import __ from 'orotranslation/js/translator';

const ItemsCountExternal = {
    /**
     * Init() function is required
     */
    init: (deferred, options) => {
        const container = document.querySelector(`[data-role="${options.gridName}-items-count"]`);

        if (document.contains(container) && options?.data?.options?.totalRecords) {
            const trans = options?.metadata?.options?.toolbarOptions?.itemsCounter?.transTemplate ??
                'oro_frontend.datagrid.pagination.totalRecords.totalRecordsShortPlural';

            container
                .querySelector('[data-role="items-count-value"]')
                .innerText = options.data.options.totalRecords;
            container.append(__(trans, {}, options.data.options.totalRecords));
        }

        return deferred.resolve();
    }
};

export default ItemsCountExternal;

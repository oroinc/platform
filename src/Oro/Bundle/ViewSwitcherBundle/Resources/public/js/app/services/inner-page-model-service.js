import InnerPageModel from 'oroviewswitcher/js/app/models/inner-page-model';
let instance;

export default {
    getModel: function() {
        if (instance) {
            return instance;
        }

        return instance = new InnerPageModel();
    }
};

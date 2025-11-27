import $ from 'jquery';
import MultiUseResourceManager from './multi-use-resource-manager';
const backdropManager = new MultiUseResourceManager({
    listen: {
        constructResource: function() {
            $(document.body).addClass('backdrop');
        },
        disposeResource: function() {
            $(document.body).removeClass('backdrop');
        }
    }
});

export default backdropManager;

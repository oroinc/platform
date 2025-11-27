import 'Base64/base64';

export default {
    encode: function(stringToEncode) {
        return window.btoa(stringToEncode);
    },
    decode: function(encodedData) {
        return window.atob(encodedData);
    }
};

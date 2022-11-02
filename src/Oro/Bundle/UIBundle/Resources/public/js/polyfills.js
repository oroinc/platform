const polyfills = [];

if (!window.sleep) {
    window.sleep = async function(duration) {
        return new Promise(resolve => setTimeout(() => resolve(0), duration));
    };
}

if (!Number.MAX_SAFE_INTEGER) {
    Number.MAX_SAFE_INTEGER = 9007199254740991; // Math.pow(2, 53) - 1;
}

export default polyfills;

import Translator from 'orotranslation/lib/translator';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

try {
    const response = await fetch(config.translationsResources);
    const translations = await response.json();
    Translator.fromJSON(translations);
} catch (error) {
    throw new Error(`Unable to load translations from "${config.translationsResources}"`);
}

export default Translator;

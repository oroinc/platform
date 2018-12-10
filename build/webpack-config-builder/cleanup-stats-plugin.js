/**
 * Removes useless output from mini-css-extract-plugin.
 * @see https://github.com/webpack-contrib/mini-css-extract-plugin/issues/168#issuecomment-420095982
 */
class CleanUpStatsPlugin {
    static shouldPickStatChild(child) {
        return child.name.indexOf('mini-css-extract-plugin') !== 0;
    }

    apply(compiler) {
        compiler.hooks.done.tap('CleanUpStatsPlugin', stats => {
            const { children } = stats.compilation;

            if (Array.isArray(children)) {
                stats.compilation.children = children.filter(child =>
                    CleanUpStatsPlugin.shouldPickStatChild(child)
                );
            }
        });
    }
}

module.exports = CleanUpStatsPlugin;

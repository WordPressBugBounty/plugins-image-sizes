// postcss.config.js
module.exports = {
    plugins: [
        require('tailwindcss'),
        require('autoprefixer'),
        require('postcss-prefix-selector')({
            prefix: '#image-sizes_render',
            transform( prefix, selector, prefixedSelector ) {
                if ( selector.startsWith( prefix ) ) return selector;
                // html/body/:root have no equivalent inside the SPA container — map to the root div.
                if ( selector === 'html' || selector === 'body' || selector === ':root' ) {
                    return prefix;
                }
                return prefixedSelector;
            },
        }),
    ]
};

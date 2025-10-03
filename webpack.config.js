const path = require('path');

module.exports = {
    entry: {
        'umw-active-alerts': './lib/src/js/umw-active-alerts.js',
        'blocks/page-alert/block': './lib/src/js/blocks/page-alert/block.js',
        'blocks/page-alert/view': './lib/src/js/blocks/page-alert/view.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'lib/dist/js'),
    },
    optimization: {
        minimize: false
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: 'babel-loader',
            },
            {
                test: /\.s[ac]ss$/i,
                use: [
                    // Creates `style` nodes from JS strings
                    'style-loader',
                    // Translates CSS into CommonJS
                    'css-loader',
                    // Compiles Sass to CSS
                    'sass-loader',
                ],
            },
            {
                test: /\.css$/i,
                use: [
                    // Creates `style` nodes from JS strings
                    'style-loader',
                    // Translates CSS into CommonJS
                    'css-loader',
                ],
            },
            {
                test: /\.html$/,
                exclude: /node_modules/,
                use: {loader: 'html-loader'}
            }
        ]
    },
};

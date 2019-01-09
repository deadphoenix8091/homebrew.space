const path = require('path');

const nodeExternals = require('webpack-node-externals');

module.exports = {
    entry: { main: './js/script.js' },
    output: {
        path: path.resolve(__dirname, 'public'),
        filename: 'bundle.js'
    },
    target: 'node',
    externals: [nodeExternals()],
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader"
                }
            },
            {
                test: /\.scss$/,
                use: [
                    "style-loader",
                    "css-loader",
                    "sass-loader"
                ]
            }
        ]
    }
};
let mix = require("laravel-mix");
let path = require("path");

mix.setPublicPath("dist")
    .js("resources/js/field.js", "js")
    .vue({ version: 3 })
    .alias({
        "laravel-nova": path.join(
            __dirname,
            "vendor/laravel/nova/resources/js/mixins/packages.js"
        ),
    })
    .webpackConfig({
        externals: {
            vue: "Vue",
        },
        output: {
            uniqueName: "marshmallow/nova-flexible",
        },
    });

mix.sass("resources/sass/field.scss", "css");
mix.css("resources/css/modal.css", "css/modal.css");

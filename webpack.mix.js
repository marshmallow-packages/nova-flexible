let mix = require("laravel-mix");
let path = require("path");
require("mix-tailwindcss");

let NovaExtension = require("laravel-nova-devtool");

mix.extend("nova", new NovaExtension());

mix.setPublicPath("dist")
    .js("resources/js/field.js", "js")
    .vue({ version: 3 })
    .css("resources/css/field.css", "css")
    .css("resources/css/modal.css", "css/modal.css")
    .tailwind()
    .alias({
        "nova-mixins": path.join(
            __dirname,
            "./vendor/laravel/nova/resources/js/mixins"
        ),
    })
    .nova("marshmallow/nova-flexible");

//

// let mix = require("laravel-mix");
// let path = require("path");

// mix.setPublicPath("dist")
//     .js("resources/js/field.js", "js")
//     .vue({ version: 3 })
//     .sass("resources/sass/field.scss", "css")
//     .css("resources/css/modal.css", "css/modal.css")
//     .alias({
//         "laravel-nova": path.join(
//             __dirname,
//             "vendor/laravel/nova/resources/js/mixins/packages.js"
//         ),
//         "nova-mixins": path.join(
//             __dirname,
//             "./vendor/laravel/nova/resources/js/mixins"
//         ),
//     })
//     .webpackConfig({
//         externals: {
//             vue: "Vue",
//             "laravel-nova": "LaravelNova",
//         },
//         output: {
//             uniqueName: "marshmallow/nova-flexible",
//         },
//     });

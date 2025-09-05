let mix = require("laravel-mix");
let path = require("path");
require("mix-tailwindcss");

let NovaExtension = require("laravel-nova-devtool");

mix.extend("nova", new NovaExtension());

mix.setPublicPath("dist")
    .js("resources/js/field.js", "js")
    .vue({ version: 3 })
    .sass("resources/sass/field.scss", "css")
    .css("resources/css/modal.css", "css/modal.css")
    .tailwind()
    .alias({
        "nova-mixins": path.join(
            __dirname,
            "./vendor/laravel/nova/resources/js/mixins"
        ),
        "laravel-nova-ui": path.join(
            __dirname,
            "./vendor/laravel/nova/resources/ui/components"
        ),
    })
    .nova("marshmallow/nova-flexible");

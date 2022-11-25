<?php

return [

    /**
     * Auto discovery will load all the flexible layouts automaticly.
     * If you want to manualy add the layouts you can do so by turning
     * this to false and add your layouts to the layouts part of this config.
     */
    'auto-discovery' => false,

    /**
     * Which WYSIWYG editor should we load for you in our default
     * layouts? You can specify your own in your custom layout. Please
     * use `config('pages.wysiwyg')::make('Content');` on your custom layouts.
     */
    'wysiwyg' => env('NOVA_WYSIWYG', \Marshmallow\Nova\TinyMCE\TinyMCE::class),

    /**
     * Your custom layouts. Please check the readme.md file for more
     * information about these custom layouts.
     */
    'layouts' => [
        // 'test' => \App\Flexible\Layouts\TestLayout::class
    ],

    'fill_empty_field' => true,
    'default_preset' => \Marshmallow\Nova\Flexible\Presets\DefaultPreset::class,

    /**
     * The default layout that will be used when creating a new flexible
     * field. This layout will be added to the flexible field when it's
     * created.
     *
     **/
    'default_layout_class' => \Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout::class,

    /**
     * If you want to add a Spatie media fields, set this to true
     */
    'has_media_library' => false,

    /**
     * If this is set to true, our default layouts will be loaded and
     * your custom layouts as specified in the array above will be merge
     * with them.
     */
    'merge_layouts' => true,
];

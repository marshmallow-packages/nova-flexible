<?php

return [

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

    /**
     * If this is set to true, our default layouts will be loaded and
     * your custom layouts as specified in the array above will be merge
     * with them.
     */
    'merge_layouts' => true,
];

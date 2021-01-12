# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2020-11-30

### Added
- It is now possible to add data to the layouts with a `with` method. Once you've done this, the `$product` variable will be available in all the layouts in this resource.
```php
$product_detail_page->flex('layout', [
    'product' => $product
])
```

### Changed
- All layouts should now extend `Marshmallow\Nova\Flexible\View\Components\Component` instead of `Illuminate\View\Component`.
- If you've update the extended class, you can remove the default `__construct` method if you like.

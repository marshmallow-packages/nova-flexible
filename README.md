![alt text](https://marshmallow.dev/cdn/media/logo-red-237x46.png "marshmallow.")

# Marshmallow Nova Flexible

This is a fork from the package of `whitecube/nova-flexible-content`. This is forked so we can build are own functionalities in to this and make it optimal for our customers.

## Installation

```bash
composer require marshmallow/nova-flexible
php artisan vendor:publish --provider="Marshmallow\Nova\Flexible\FieldServiceProvider"
```

## Table of Contents

-   [Prepare](#Prepare)
    -   [Nova Resource](#Prepare)
    -   [Models](#Models)
-   [Commands](#Commands)
-   [Customize](#Customize)
    -   [Title on Layout classes](#Customize)
    -   [Title on custom layouts](#Customize)
-   [Helpers](#Helpers)

## Commands

### Create layout

To create a new layout you can run the command below.

```bash
php artisan make:flex
```

## Prepare

### Nova Resource

You can use the `getFlex()` method to get all the layouts that are provided with this package and the layouts that you created yourself. If you want to manualy add flexibles to your Nova resource, please reference the manual usage section of this readme.

```php
class Page extends Resource
{
	use \Marshmallow\Nova\Flexible\Nova\Traits\HasFlexable;

	public function fields(Request $request)
	{
		return [
			// ...
			$this->getFlex(),
			// ...
		];
	}
}
```

### Models

Make sure your model will cast this data correctly by adding this to the field of your model.

```php
class Page extends Model
{
	use \Marshmallow\Nova\Flexible\Concerns\HasFlexible;

	protected $casts = [
		/**
		 * 'layout' in the example below references the field
		 * in the model where the json is stored.
		 */
		'layout' => \Marshmallow\Nova\Flexible\Casts\FlexibleCast::class,
	];
}
```

## Customize

### Title on Layout classes

Sometimes its very hard to see which group is what because all items look the same. It is possible to get a value from one of the fields to be displayed in the group overview. By default the `title` field will be used but it can be overriten.

```php
// Layout class
// In a layout class you just set the $titleFromContent property to the field name you
// wish it shows.
protected $titleFromContent = 'title';
```

### Title on custom layouts

```php
/**
 * For custom layout, we need to add a 4th parameter. This should be a callable and call the setTitleFromContent method.
 */
->addLayout(
	'Mr. Mallow',
	'mr_mallow', [
        Text::make('Title', 'title'),
        Text::make('Sub title', 'sub_title'),
        // ...
	],
	function ($layout) {
		return $layout->setTitleFromContent('sub_title');
    }
)


/**
 * You can also use a callback to build your title.
 */
->addLayout(
	'Mr. Mallow',
	'mr_mallow', [
        Text::make('Title', 'title'),
        Date::make('Date', 'date'),
        Textarea::make('Content', 'content'),
        // ...
	],
	function ($layout) {
		return $layout->resolveTitleUsing(function ($title, $date, $content) {
            return $title . ' ' . Carbon::parse($date)->format('Y-m-d');
        });
    }
)
```

## Helpers

You can overrule a lot of defaults with the methods below.

```php
Flexible::make(__('Marshmallow'), 'marshmallow')
    ->addLayout(__('Mr. Mallow'), 'mr_mallow', [
        //
    ])

    // Don't use the component selector but a simple dropdown menu
    ->simpleMenu()

    // Use the full with of the nova container
    ->fullWidth()

    // Collapse all layouts when they are loaded
    ->collapsed()

    // Change the text in the add more layouts button
    ->button(__('Add comment'))

    // Disable deleting items
    ->deletionNotAllowed();
```

---

## Credits

See https://github.com/whitecube/nova-flexible-content - [Whitecube](https://github.com/whitecube)

Copyright (c) 2020 marshmallow.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

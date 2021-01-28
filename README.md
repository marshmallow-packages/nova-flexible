![alt text](https://cdn.marshmallow-office.com/media/images/logo/marshmallow.transparent.red.png "marshmallow.")

# Marshmallow Nova Flexable
This is a fork from the package of `whitecube/nova-flexible-content`. This is forked so we can build are own functionalities in to this and make it optimal for our customers.

### Installatie
```
composer require marshmallow/nova-flexible
```

### Publish the config
You need to publish the config to add more layouts to your flex. Please run the command below:
```bash
php artisan vendor:publish --provider="Marshmallow\Nova\Flexible\FieldServiceProvider"
```

### Prepare your Nova Resource
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

### Prepare your models
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

### Display value in groups
Sometimes its very hard to see which group is what because all items look the same. It is possible to get a value from one of the fields to be displayed in the group overview. By default the title field will be used but it can be overriten.

```php
// Layout class
// In a layout class you just set the $titleFromContent property to the field name you
// wish it shows.
protected $titleFromContent = 'title';

// Custom layout
// For custom layout, we need to add a 4th parameter. This should be a callable and call
// the setTitleFromContent method.
->addLayout(
	'USP',
	'uspfontawesome',
	[
        Text::make('Title'),
        Text::make('Title2'),
        Fontawesome::make('Icon'),
        config('pages.wysiwyg')::make('Content'),
	],
	function ($created_layout) {
		return $created_layout->setTitleFromContent('title2');
    }
)
```

## Disable deleting
```php
$flex->addLayout($layout)
     ->deletionNotAllowed();
```

- - -

## Credits
See https://github.com/whitecube/nova-flexible-content - [Whitecube](https://github.com/whitecube)

Copyright (c) 2020 marshmallow.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

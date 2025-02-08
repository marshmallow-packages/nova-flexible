![alt text](https://marshmallow.dev/cdn/media/logo-red-237x46.png "marshmallow.")

# Marshmallow Nova Flexible

> [!important]
> This package was originally forked from [whitecube/nova-flexible-content](<[https://github.com/outl1ne/nova-multiselect-field](https://github.com/whitecube/nova-flexible-content)>). Since we were making many opinionated changes, we decided to continue development in our own version rather than submitting pull requests that might not benefit all users of the original package. You’re welcome to use this package—we’re actively maintaining it. If you encounter any issues, please don’t hesitate to reach out.

## Installation

```bash
composer require marshmallow/nova-flexible
php artisan vendor:publish --provider="Marshmallow\Nova\Flexible\FieldServiceProvider"
```

To use the MarshmallowMediaLayout, this packages requires 'spatie/laravel-medialibrary', see the insturctions for installation on the [spatie website](https://spatie.be/docs/laravel-medialibrary/v10/installation-setup)
For Nova use 'marshmallow/advanced-nova-media-library' and follow the instructions on the [github page](https://github.com/marshmallow-packages/advanced-nova-media-library)

## Table of Contents

-   [Prepare](#Prepare)
    -   [Nova Resource](#Prepare)
    -   [Models](#Models)
-   [Commands](#Commands)
-   [Customize](#Customize)
    -   [Title on Layout classes](#Customize)
    -   [Title on custom layouts](#Customize)
    -   ['Config methods'](#ConfigMethods)
-   [Helpers](#Helpers)

## Commands

### Create layout

To create a new layout you can run the command below.

```bash
php artisan make:flex Element\\Counter --livewire
```

### Create templates

```bash
php artisan make:flex Forms\\Newsletter --template=newsletter
php artisan make:flex Forms\\Contact --template=form
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

<a id="ConfigMethods"></a>

## Config Methods

You can call a load config method once you've created your flexible field to change the behaviour of the flexible field. Below you will find some examples.

```php
$this->getFlex(__('Layout'), 'layout')
    ->loadConfig([
        'simpleView' => null,
        'allowedToCreate' => ['allowed' => true|false],
        'allowedToDelete' => ['allowed' => true|false],
        'allowedToChangeOrder' => ['allowed' => true|false],
        'simpleMenu' => null,
        'button' => ['label' => 'Button Label'],
        'fullWidth' => ['fullWidth' => true|false],
        'limit' => ['limit' => 3],
    ]),
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

#### The Layouts Collection

Collections returned by the `FlexibleCast` cast and the `HasFlexible` trait extend the original `Illuminate\Support\Collection`. These custom layout collections expose a `find(string $name)` method which returns the first layout having the given layout `$name`.

#### The Layout instance

Layouts are some kind of _fake models_. They use Laravel's `HasAttributes` trait, which means you can define accessors & mutators for the layout's attributes. Furthermore, it's also possible to access the Layout's properties using the following methods:

##### `name()`

Returns the layout's name.

##### `title()`

Returns the layout's title (as shown in Nova).

##### `key()`

Returns the layout's unique key (the layout's unique identifier).

## Going further

When using the Flexible Content field, you'll quickly come across of some use cases where the basics described above are not enough. That's why we developed the package in an extendable way, making it possible to easily add custom behaviors and/or capabilities to Field and its output.

### Custom Layout Classes

Sometimes, `addLayout` definitions can get quite long, or maybe you want them to be shared with other `Flexible` fields. The answer to this is to extract your Layout into its own class. [See the docs for more information on this](https://Marshmallow.github.io/nova-flexible-content/#/?id=custom-layout-classes).

### Predefined Preset Classes

In addition to reusable Layout classes, you can go a step further and create `Preset` classes for your Flexible fields. These allow you to reuse your whole Flexible field anywhere you want. They also make it easier to make your Flexible fields dynamic, for example if you want to add Layouts conditionally. And last but not least, they also have the added benefit of cleaning up your Nova Resource classes, if your Flexible field has a lot of `addLayout` definitions. [See the docs for more information on this](https://Marshmallow.github.io/nova-flexible-content/#/?id=predefined-preset-classes).

### Custom Resolver Classes

By default, the field takes advantage of a **JSON column** on your model's table. In some cases, you'd really like to use this field, but for some reason a JSON attribute is just not the way to go. For example, you could want to store the values in another table (meaning you'll be using the Flexible Content field instead of a traditional BelongsToMany or HasMany field). No worries, we've got you covered!

Tell the field how to store and retrieve its content by creating your own Resolver class, which basically just contains two simple methods: `get` and `set`. [See the docs for more information on this](https://Marshmallow.github.io/nova-flexible-content/#/?id=custom-resolver-classes).

### Usage with nova-page

Maybe you heard of one of our other packages, [nova-page](https://github.com/Marshmallow/nova-page), which is a Nova Tool that allows to edit static pages such as an _"About"_ page (or similar) without having to declare a model for it individually. More often than not, the Flexible Content Field comes in handy. Don't worry, both packages work well together! First create a [nova page template](https://github.com/Marshmallow/nova-page#creating-templates) and add a [flexible content](https://github.com/Marshmallow/nova-flexible-content#adding-layouts) to the template's fields.

As explained in the documentation, you can [access nova-page's static content](https://github.com/Marshmallow/nova-page#accessing-the-data-in-your-views) in your blade views using `{{ Page::get('attribute') }}`. When requesting the flexible content like this, it returns a raw JSON string describing the flexible content, which is of course not very useful. Instead, you can simply implement the `Marshmallow\Nova\Flexible\Concerns\HasFlexible` trait on your page Templates, which will expose the `Page::flexible('attribute')` facade method and will take care of the flexible content's transformation.

```php
namespace App\Nova\Templates;

// ...
use Marshmallow\Nova\Flexible\Concerns\HasFlexible;

class Home extends Template
{
    use HasFlexible;

    // ...
}
```

## 💖 Sponsorships

If you are reliant on this package in your production applications, consider [sponsoring us](https://github.com/sponsors/Marshmallow)! It is the best way to help us keep doing what we love to do: making great open source software.

## Contributing

Feel free to suggest changes, ask for new features or fix bugs yourself. We're sure there are still a lot of improvements that could be made, and we would be very happy to merge useful pull requests.

Thanks!

### Unit tests

When adding a new feature or fixing a bug, please add corresponding unit tests. The current set of tests is limited, but every unit test added will improve the quality of the package.

Run PHPUnit by calling `composer test`.

## Made with ❤️ for open source

At [Marshmallow](https://www.Marshmallow.be) we use a lot of open source software as part of our daily work.
So when we have an opportunity to give something back, we're super excited!

We hope you will enjoy this small contribution from us and would love to [hear from you](mailto:hello@Marshmallow.be) if you find it useful in your projects. Follow us on [Twitter](https://twitter.com/Marshmallow_be) for more updates!

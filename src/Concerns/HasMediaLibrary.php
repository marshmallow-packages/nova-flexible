<?php

namespace Marshmallow\Nova\Flexible\Concerns;

use Laravel\Nova\Nova;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\Nova\Flexible\Flexible;
use Laravel\Nova\Http\Requests\NovaRequest;
use Spatie\MediaLibrary\InteractsWithMedia;
use Marshmallow\Nova\Flexible\Layouts\Layout;
use Ebess\AdvancedNovaMediaLibrary\Fields\Media;
use Marshmallow\Nova\Flexible\Http\ScopedRequest;
use Marshmallow\Nova\Flexible\FileAdder\FileAdder;
use Spatie\MediaLibrary\Downloaders\DefaultDownloader;
use Marshmallow\Nova\Flexible\FileAdder\FileAdderFactory;
use Spatie\MediaLibrary\MediaCollections\MediaRepository;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidUrl;

trait HasMediaLibrary
{
    use InteractsWithMedia;

    /**
     * Return the underlying model implementing the HasMedia interface
     *
     * @return \Spatie\MediaLibrary\HasMedia
     */
    protected function getUnderlyingMediaModel(): HasMedia
    {
        $model = Flexible::getOriginModel() ?? $this->model;

        while ($model instanceof Layout) {
            if (method_exists($model, 'getMediaModel')) {
                $model = $model->getMediaModel();
            } else {
                // If getMediaModel method doesn't exist, break out of the loop
                // and use the current model or fallback to the original model
                $model = $this->model ?? Flexible::getOriginModel();
                break;
            }
            
            // Ensure we don't get stuck in an infinite loop with invalid returns
            if (is_array($model) || (!$model instanceof Layout && !$model instanceof HasMedia)) {
                $model = $this->model ?? Flexible::getOriginModel();
                break;
            }
        }

        if (is_null($model) || !($model instanceof HasMedia)) {
            throw new \Exception('Origin HasMedia model not found.');
        }

        return $model;
    }

    /**
     * Add a file to the medialibrary.
     *
     * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return \Spatie\MediaLibrary\MediaCollections\FileAdder
     */
    public function addMedia($file): \Spatie\MediaLibrary\MediaCollections\FileAdder
    {
        return app(FileAdderFactory::class)
            ->create($this->getUnderlyingMediaModel(), $file, $this->getSuffix())
            ->preservingOriginal();
    }

    /**
     * This is a slightly altered version of Spatie's addMediaFromUrl, tweaked
     * based on the overridden addMedia method in this class.
     *
     * @param string $url
     *
     * @param string|array<string> ...$allowedMimeTypes
     */
    public function addMediaFromUrl($url, ...$allowedMimeTypes): \Spatie\MediaLibrary\MediaCollections\FileAdder
    {
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            throw InvalidUrl::doesNotStartWithProtocol($url);
        }

        $downloader = config(
            'media-library.media_downloader',
            DefaultDownloader::class
        );
        $temporaryFile = (new $downloader())->getTempFile($url);
        $this->guardAgainstInvalidMimeType($temporaryFile, $allowedMimeTypes);

        $filename = basename(parse_url($url, PHP_URL_PATH));
        $filename = urldecode($filename);

        if ($filename === '') {
            $filename = 'file';
        }

        $mediaExtension = explode('/', mime_content_type($temporaryFile));

        if (!Str::contains($filename, '.')) {
            $filename = "{$filename}.{$mediaExtension[1]}";
        }

        return app(FileAdderFactory::class)
            ->create($this->getUnderlyingMediaModel(), $temporaryFile, $this->getSuffix())
            ->usingName(pathinfo($filename, PATHINFO_FILENAME))
            ->usingFileName($filename);
    }
    /**
     * Get media collection by its collectionName.
     *
     * @param string $collectionName
     * @param array|callable $filters
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMedia(string $collectionName = 'default', $filters = []): Collection
    {
        return app(MediaRepository::class)
            ->getCollection($this->getUnderlyingMediaModel(), $collectionName . $this->getSuffix(), $filters);
    }

    /**
     * Get the media collection name suffix.
     *
     * @return string
     */
    public function getSuffix()
    {
        return '_' . $this->inUseKey();
    }

    /**
     * Resolve fields for display using given attributes.
     *
     * @param array $attributes
     * @return array
     */
    public function resolveForDisplay(array $attributes = [])
    {
        $this->fields->each(function ($field) use ($attributes) {
            if (is_a($field, Media::class) || is_a($field, \Marshmallow\AdvancedNovaMediaLibrary\Fields\Media::class)) {
                $field->resolveForDisplay($this->getUnderlyingMediaModel(), $field->attribute . $this->getSuffix());
            } else {
                $field->resolveForDisplay($attributes);
            }
        });

        return $this->getResolvedValue();
    }

    /**
     * The default behaviour when removed
     * Should remove all related medias except if shouldDeletePreservingMedia returns true
     *
     * @param  Flexible $flexible
     * @param  Marshmallow\Nova\Flexible\Layout $layout
     *
     * @return mixed
     */
    protected function removeCallback(Flexible $flexible, Layout $layout)
    {
        if ($this->shouldDeletePreservingMedia()) return;

        $collectionsToClear = config('media-library.media_model')::select('collection_name')
            ->where('collection_name', 'like', '%' . $this->getSuffix())
            ->distinct()
            ->pluck('collection_name')
            ->map(function ($value) {
                return str_replace($this->getSuffix(), '', $value);
            });

        foreach ($collectionsToClear as $collection) {
            $this->getUnderlyingMediaModel()->clearMediaCollection($collection);
        }
    }
}

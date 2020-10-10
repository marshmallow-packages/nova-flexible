<?php

namespace Marshmallow\Nova\Flexible\Concerns;

use Ebess\AdvancedNovaMediaLibrary\Fields\Media;
use Illuminate\Support\Collection;
use Marshmallow\Nova\Flexible\FileAdder\FileAdderFactory;
use Marshmallow\Nova\Flexible\Flexible;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\MediaRepository;

trait HasMediaLibrary
{
    use InteractsWithMedia;

    /**
     * Return the underlying model implementing the HasMedia interface
     *
     * @return \Spatie\MediaLibrary\HasMedia
     */
    protected function getMediaModel() : HasMedia
    {
        $model = Flexible::getOriginModel() ?? $this->model;

        if (is_null($model) || ! ($model instanceof HasMedia)) {
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
    public function addMedia($file) : \Spatie\MediaLibrary\MediaCollections\FileAdder
    {
        return app(FileAdderFactory::class)
            ->create($this->getMediaModel(), $file, $this->getSuffix())
            ->preservingOriginal();
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
            ->getCollection($this->getMediaModel(), $collectionName . $this->getSuffix(), $filters);
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
            if (is_subclass_of($field, Media::class)) {
                $field->resolveForDisplay($this->getMediaModel(), $field->attribute . $this->getSuffix());
            } else {
                $field->resolveForDisplay($attributes);
            }
        });

        return $this->getResolvedValue();
    }
}

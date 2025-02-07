<?php

namespace Druc\Langscanner;

use Illuminate\Filesystem\Filesystem;
use Webmozart\Assert\Assert;

class FileTranslations implements Contracts\FileTranslations
{
    private string $language;
    private string $rootPath;
    private bool $saveDottedItemsAsArray;
    private Filesystem $disk;

    public function __construct(array $opts)
    {
        Assert::keyExists($opts, 'language');

        $this->language = $opts['language'];
        $this->disk = $opts['disk'] ?? resolve(Filesystem::class);
        $this->rootPath = $opts['rootPath'] ?? config('langscanner.lang_dir_path') . '/';
        $this->saveDottedItemsAsArray = config('langscanner.save_dotted_items_as_array', false);
    }

    public function language(): string
    {
        return $this->language;
    }

    public function update(array $translations): void
    {
        $translations = array_merge($this->all(), $translations);

        if ($this->saveDottedItemsAsArray) {
            $translations = collect($translations)->undot()->toArray();
        }

        $translations = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->disk->put($this->path(), $translations);
    }

    public function all(): array
    {
        $translations = [];

        if (file_exists($this->path())) {
            $translations = json_decode($this->disk->get($this->path()), true);

            if ($this->saveDottedItemsAsArray) {
                $translations = collect($translations)->dot()->toArray();
            }
        }

        return $translations;
    }

    public function contains(string $key): bool
    {
        return !empty($this->all()[$key]);
    }

    private function path(): string
    {
        return $this->rootPath . "{$this->language()}.json";
    }
}

<?php

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Enums\PermissionGroup;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use Illuminate\Support\Arr;

/**
 * @return array<string, string> dotted key => value of every PHP file of the locale
 */
function translationsOf(string $locale): array
{
    $translations = [];

    foreach (glob(lang_path($locale.'/*.php')) ?: [] as $file) {
        $group = basename($file, '.php');
        $translations += Arr::dot([$group => require $file]);
    }

    return $translations;
}

/**
 * @return list<string> the placeholders in a line, sorted
 */
function placeholdersOf(string $line): array
{
    preg_match_all('/:([a-z_]+)/', $line, $matches);
    $placeholders = array_values(array_unique($matches[1]));
    sort($placeholders);

    return $placeholders;
}

test('every supported language has the same keys and placeholders as English', function () {
    $english = translationsOf('en');

    // Laravel's own validation, password and pagination lines fall back to the
    // framework's English, so only the other language ships them.
    $ownGroups = fn (array $lines) => array_filter(
        $lines,
        fn (string $key) => ! str_starts_with($key, 'validation.') && ! str_starts_with($key, 'passwords.') && ! str_starts_with($key, 'pagination.'),
        ARRAY_FILTER_USE_KEY,
    );

    foreach (array_diff(config('app.supported_locales'), ['en']) as $locale) {
        $other = translationsOf($locale);

        expect(array_keys($ownGroups($other)))->toEqualCanonicalizing(array_keys($ownGroups($english)));

        foreach ($ownGroups($english) as $key => $line) {
            expect(placeholdersOf((string) $other[$key]))->toBe(placeholdersOf((string) $line), "Placeholders of {$locale}.{$key} differ");
        }
    }
});

test('every translation key used in the frontend exists', function () {
    $english = translationsOf('en');
    $missing = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('js')));

    foreach ($files as $file) {
        if (! preg_match('/\.tsx?$/', $file->getFilename()) || str_contains($file->getPathname(), '/types/generated.ts')) {
            continue;
        }

        preg_match_all('/\bt(?:Choice)?\(\s*[\'"]([a-z_]+\.[a-zA-Z0-9_. ]+)[\'"]/', (string) file_get_contents($file->getPathname()), $matches);

        foreach ($matches[1] as $key) {
            if (! array_key_exists($key, $english)) {
                $missing[] = $key.' in '.$file->getFilename();
            }
        }
    }

    expect($missing)->toBe([]);
});

test('every enum shown in the interface has a label', function (string $prefix, array $values) {
    foreach (['en', ...config('app.supported_locales')] as $locale) {
        $lines = translationsOf($locale);

        foreach ($values as $value) {
            expect($lines)->toHaveKey("{$prefix}.{$value}");
        }
    }
})->with([
    'activity events' => ['activity.events', array_column(ActivityEvent::cases(), 'value')],
    'activity sources' => ['activity.sources', array_column(ActivitySource::cases(), 'value')],
    'permissions' => ['roles.permissions', array_column(Permission::cases(), 'value')],
    'permission groups' => ['roles.groups', array_column(PermissionGroup::cases(), 'value')],
]);

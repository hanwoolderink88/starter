<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

/**
 * How one field of a CollaborativeModel is edited together.
 */
final readonly class CollaborativeField
{
    /**
     * @param  array<int, mixed>  $rules  the validation rules of every save, as for the field in a form
     * @param  string  $label  the field's name in validation messages, such as `roles.form.name`; a translation key or plain text
     * @param  bool  $multiline  a long text (textarea): it keeps a history of revisions that can be compared and restored
     */
    public function __construct(
        public array $rules,
        public string $label,
        public bool $multiline = false,
    ) {}
}

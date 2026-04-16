<?php

declare(strict_types=1);

namespace App\Domain\Spells\Validation;

final readonly class ValidationResult
{
    /** @param list<string> $errors */
    public function __construct(
        private array $errors,
    ) {}

    public static function ok(): self
    {
        return new self([]);
    }

    /** @param list<string> $errors */
    public static function fail(array $errors): self
    {
        return new self($errors);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }
}

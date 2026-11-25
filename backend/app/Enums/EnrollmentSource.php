<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Doku = 'doku';
    // Buyers migrated from the Scalev checkout that this LMS replaces.
    case Scalev = 'scalev';
    case Manual = 'manual';
    // Free access the team gives itself from the panel; never tied to an order.
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Doku => 'DOKU',
            self::Scalev => 'Scalev',
            self::Manual => 'Manual',
            self::Internal => 'Tim internal',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}

<?php

namespace App\Enums;

enum Salutation: string
{
    // phpcs:disable
    case Mr = 'mr';
    case Mrs = 'mrs';
    case Miss = 'miss';
    case Dr = 'dr';
    case Sir = 'sir';
    case Madam = 'madam';
    // phpcs:enable

    // This method is used to display the enum value in the user interface.
    public function label(): string
    {
        return match ($this) {
            self::Mr => __('app.' . $this->value),
            self::Mrs => __('app.' . $this->value),
            self::Miss => __('app.' . $this->value),
            self::Dr => __('app.' . $this->value),
            self::Sir => __('app.' . $this->value),
            self::Madam => __('app.' . $this->value),
            default => $this->value,
        };
    }

}

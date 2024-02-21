<?php

namespace App\Enums;

enum MaritalStatus: string
{
    // phpcs:disable
    case Single = 'single';
    case Married = 'married';
    case Widower = 'widower';
    case Widow = 'widow';
    case Separate = 'separate';
    case Divorced = 'divorced';
    case Engaged = 'engaged';
    // phpcs:enable

    // This method is used to display the enum value in the user interface.
    public function label(): string
    {
        return match ($this) {
            self::Single => __('app.maritalStatus.' . $this->value),
            self::Married => __('app.maritalStatus.' . $this->value),
            self::Widower => __('app.maritalStatus.' . $this->value),
            self::Widow => __('app.maritalStatus.' . $this->value),
            self::Separate => __('app.maritalStatus.' . $this->value),
            self::Divorced => __('app.maritalStatus.' . $this->value),
            self::Engaged => __('app.maritalStatus.' . $this->value),
            default => $this->value,
        };
    }

    // This method is return all the values as array.
    public static function toArray(): array
    {
        $maritalStatus = [];

        foreach (MaritalStatus::cases() as $status) {
            $maritalStatus [] = $status->value;
        }

        return $maritalStatus;
    }

}

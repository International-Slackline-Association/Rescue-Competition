<?php

namespace App\Enums;

enum RescueRole: string
{
    case Victim = 'victim';
    case LineRescuer = 'line_rescuer';
    case GroundRescuer = 'ground_rescuer';

    public function label(): string
    {
        return match ($this) {
            self::Victim => 'Victim',
            self::LineRescuer => 'On-the-line rescuer',
            self::GroundRescuer => 'Ground rescuer',
        };
    }

    /**
     * Legend colour from the highline diagram: harness (PPE), main line, structural support.
     */
    public function chipClass(): string
    {
        return match ($this) {
            self::Victim => 'bg-ppe',
            self::LineRescuer => 'bg-main',
            self::GroundRescuer => 'bg-support',
        };
    }
}

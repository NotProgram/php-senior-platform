<?php

declare(strict_types=1);

namespace App\Enum;

enum ProgressStatus: string
{
    case Locked = 'LOCKED';
    case Available = 'AVAILABLE';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Mastered = 'MASTERED';

    public function label(): string
    {
        return match ($this) {
            self::Locked => 'Bloqueado',
            self::Available => 'Disponible',
            self::InProgress => 'En progreso',
            self::Completed => 'Completado',
            self::Mastered => 'Dominado',
        };
    }

    public function badgeStyle(): string
    {
        return match ($this) {
            self::Locked => 'background: rgba(255,255,255,0.05); color: #777777; border: 1px solid #3c3c3c;',
            self::Available => 'background: rgba(0, 122, 204, 0.15); color: #4fc1ff; border: 1px solid rgba(0, 122, 204, 0.4);',
            self::InProgress => 'background: rgba(220, 220, 170, 0.15); color: #dcdcaa; border: 1px solid rgba(220, 220, 170, 0.4);',
            self::Completed => 'background: rgba(137, 209, 133, 0.15); color: #89d185; border: 1px solid rgba(137, 209, 133, 0.4);',
            self::Mastered => 'background: rgba(206, 145, 120, 0.2); color: #e5a085; border: 1px solid rgba(206, 145, 120, 0.5); font-weight: 600;',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Locked => 'shield',
            self::Available => 'circle',
            self::InProgress => 'play',
            self::Completed => 'check-circle',
            self::Mastered => 'award',
        };
    }

    public function isAccessible(): bool
    {
        return $this !== self::Locked;
    }
}

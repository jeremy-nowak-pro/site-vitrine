<?php

namespace App\Enums;

enum TypeDocument: string
{
    case NoticeMontage = 'notice_montage';
    case GuidePeinture = 'guide_peinture';
    case ProcedureAtelier = 'procedure_atelier';
    case FicheTechnique = 'fiche_technique';

    public function libelle(): string
    {
        return match ($this) {
            self::NoticeMontage => 'Notice de montage',
            self::GuidePeinture => 'Guide de peinture',
            self::ProcedureAtelier => 'Procédure atelier',
            self::FicheTechnique => 'Fiche technique',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->libelle()],
            self::cases(),
        );
    }
}

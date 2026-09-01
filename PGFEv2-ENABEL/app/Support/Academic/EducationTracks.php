<?php

declare(strict_types=1);

namespace App\Support\Academic;

final class EducationTracks
{
    public const MATERNELLE = 'maternelle';

    public const PRIMAIRE = 'primaire';

    public const BASE_7_8 = 'base_7_8';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [self::MATERNELLE, self::PRIMAIRE, self::BASE_7_8];
    }

    /**
     * Humanités / technique : 1ère à 4ème (plus de 5ème-6ème).
     *
     * @return list<string>
     */
    public static function secondaryLevels(): array
    {
        return ['1ère', '2ème', '3ème', '4ème'];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function secondaryCycles(): array
    {
        $levels = self::secondaryLevels();

        return [
            'Long' => $levels,
            'Court' => $levels,
        ];
    }

    /**
     * Anciens libellés de 5ème/6ème (cycle long technique).
     *
     * @return list<string>
     */
    public static function obsoleteSecondaryLevelNames(): array
    {
        return ['5eme', '5ème', '5em', '6eme', '6ème', '6em'];
    }

    /**
     * Alias reconnus pour ne pas dupliquer 1er / 1ère, etc.
     *
     * @return array<string, list<string>>
     */
    public static function secondaryLevelAliases(): array
    {
        return [
            '1ère' => ['1ère', '1ere', '1er', '1'],
            '2ème' => ['2ème', '2eme', '2nd', '2n', '2'],
            '3ème' => ['3ème', '3eme', '3em', '3'],
            '4ème' => ['4ème', '4eme', '4em', '4'],
        ];
    }

    /**
     * @return array<string, array{section: array{name: string, code: string}, cycle: string, levels: list<string>}>
     */
    public static function basicTracks(): array
    {
        return [
            self::MATERNELLE => [
                'section' => ['name' => 'Maternelle', 'code' => 'MAT'],
                'cycle' => 'Maternelle',
                'levels' => ['1ère maternelle', '2ème maternelle', '3ème maternelle'],
            ],
            self::PRIMAIRE => [
                'section' => ['name' => 'Primaire', 'code' => 'PRI'],
                'cycle' => 'Primaire',
                'levels' => [
                    '1ère primaire',
                    '2ème primaire',
                    '3ème primaire',
                    '4ème primaire',
                    '5ème primaire',
                    '6ème primaire',
                ],
            ],
            self::BASE_7_8 => [
                'section' => ['name' => 'Enseignement de base', 'code' => 'EB'],
                'cycle' => '7e et 8e de base',
                'levels' => ['7ème de base', '8ème de base'],
            ],
        ];
    }
}

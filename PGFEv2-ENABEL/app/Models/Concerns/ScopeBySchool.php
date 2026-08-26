<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\Organization\SchoolScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Global scope conditionnel multi-école.
 * - Filtre automatiquement par school_id de l'utilisateur connecté (si défini).
 * - Ne filtre pas si rôle admin (global) ou utilisateur sans school_id.
 */
trait ScopeBySchool
{
    public static function bootScopeBySchool(): void
    {
        static::addGlobalScope('by_school', function (Builder $builder): void {
            $user = Auth::user();
            if (! $user) {
                return; // invité => pas de filtrage (géré par middleware d'auth en amont)
            }
            $resolver = app(SchoolScopeResolver::class);
            $allowedSchoolIds = $resolver->allowedSchoolIds($user);

            if ($allowedSchoolIds === null) {
                return;
            }

            if ($allowedSchoolIds === []) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $model = $builder->getModel();
            $table = $model->getTable();

            $applySchoolFilter = function (Builder $builder, string $column = 'school_id') use ($allowedSchoolIds): void {
                if (count($allowedSchoolIds) === 1) {
                    $builder->where($column, $allowedSchoolIds[0]);

                    return;
                }
                $builder->whereIn($column, $allowedSchoolIds);
            };

            // 1) Filtrer directement par colonne school_id si disponible
            try {
                if (Schema::hasColumn($table, 'school_id')) {
                    $applySchoolFilter($builder, $table.'.school_id');

                    return;
                }
            } catch (Throwable $e) {}

            // 2) Filiaire : relation directe school_id
            if ($model instanceof \App\Models\Filiaire) {
                $applySchoolFilter($builder, 'school_id');

                return;
            }

            // 3) Cycle : via filiaire.school_id
            if ($model instanceof \App\Models\Cycle) {
                $builder->whereHas('filiaire', function (Builder $q) use ($applySchoolFilter): void {
                    $applySchoolFilter($q);
                });

                return;
            }

            // 4) AcademicLevel : via cycle.filiaire.school_id
            if ($model instanceof \App\Models\AcademicLevel) {
                $builder->whereHas('cycle.filiaire', function (Builder $q) use ($applySchoolFilter): void {
                    $applySchoolFilter($q);
                });

                return;
            }

            // 5) Classroom : via academicLevel.cycle.filiaire.school_id
            if ($model instanceof \App\Models\Classroom) {
                $builder->whereHas('academicLevel.cycle.filiaire', function (Builder $q) use ($applySchoolFilter): void {
                    $applySchoolFilter($q);
                });

                return;
            }

            // 6) Journal comptable : via InputAccount / OutputAccount / AccountPlan
            if ($model instanceof \App\Models\Journal) {
                $builder->where(function (Builder $q) use ($applySchoolFilter): void {
                    $q->whereHas('inputAccount', function (Builder $q2) use ($applySchoolFilter): void {
                        $applySchoolFilter($q2);
                    })->orWhereHas('outputAccount', function (Builder $q2) use ($applySchoolFilter): void {
                        $applySchoolFilter($q2);
                    })->orWhereHas('accountPlan', function (Builder $q2) use ($applySchoolFilter): void {
                        $applySchoolFilter($q2);
                    });
                });

                return;
            }

            // Sinon: pas de filtrage appliqué (modèle non relié à une école)
        });
    }

    /**
     * Permet de supprimer le scope manuellement si besoin.
     */
    public static function withoutSchoolScope(): callable
    {
        return function (Builder $builder): Builder {
            return $builder->withoutGlobalScope('by_school');
        };
    }
}

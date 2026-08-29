<?php

declare(strict_types=1);

namespace App\Services\MenuService;

use Exception;
use Illuminate\Support\Facades\Request;

final class AdminMenuService
{
    /**
     * Retourne un tableau associatif: group => [AdminMenuItem, ...]
     * Ici on ne définit que le module Academic Levels (peut être étendu ensuite).
     *
     * @return array<string, AdminMenuItem[]>
     */
    public function getMenu(): array
    {
        $groups = [];
        $user = auth()->user();
        $selectedSchoolId = session('selected_school_id');

        $groups['Dashboard'] = [
            new AdminMenuItem([
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'lucide:layout-dashboard',
                'route' => $this->getRouteWithContext('admin.dashboard'),
                'active' => $this->isCurrentRoutePrefixed('admin.dashboard'),
            ]),
        ];

        // admin-proved : menu permanent (pas de drill-down module) — écoles uniquement (sous-divisions masquées)
        if ($user && $user->hasRole('admin-proved') && ! $user->hasRole('super-admin')) {
            $groups['Organisation'] = [
                new AdminMenuItem([
                    'id' => 'organisation-schools',
                    'label' => 'Écoles',
                    'icon' => 'mdi:school',
                    'route' => $this->getRouteWithContext('admin.schools.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.schools.'),
                ]),
            ];

            $groups['Collecte rapide'] = [
                new AdminMenuItem([
                    'id' => 'collecte-rapides',
                    'label' => 'Saisies',
                    'icon' => 'lucide:clipboard-list',
                    'route' => $this->getRouteWithContext('admin.collecte-rapides.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.collecte-rapides.')
                        && ! Request::routeIs('admin.collecte-rapides.synthese'),
                ]),
                new AdminMenuItem([
                    'id' => 'collecte-rapides-stats',
                    'label' => 'Stats',
                    'icon' => 'lucide:bar-chart-3',
                    'route' => $this->getRouteWithContext('admin.collecte-rapides.synthese'),
                    'active' => Request::routeIs('admin.collecte-rapides.synthese'),
                ]),
            ];

            return $groups;
        }

        if ($user && $user->hasAnyRole(['super-admin', 'admin-sous-division'])) {
            $organisationItems = [];

            $organisationItems[] = new AdminMenuItem([
                'id' => 'organisation-schools',
                'label' => 'Écoles',
                'icon' => 'mdi:school',
                'route' => $this->getRouteWithContext('admin.schools.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.schools.'),
            ]);

            $groups['Organisation'] = $organisationItems;
        }

        $groups['Administration'] = [
            new AdminMenuItem([
                'id' => 'countries',
                'label' => 'Pays',
                'icon' => 'lucide:globe',
                'route' => $this->getRouteWithContext('admin.countries.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.countries.'),
            ]),
            new AdminMenuItem([
                'id' => 'provinces',
                'label' => 'Provinces',
                'icon' => 'lucide:map-pin',
                'route' => $this->getRouteWithContext('admin.provinces.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.provinces.'),
            ]),
            new AdminMenuItem([
                'id' => 'communes',
                'label' => 'Communes',
                'icon' => 'lucide:map',
                'route' => $this->getRouteWithContext('admin.communes.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.communes.'),
            ]),
            new AdminMenuItem([
                'id' => 'territories',
                'label' => 'Territoires',
                'icon' => 'lucide:map-pinned',
                'route' => $this->getRouteWithContext('admin.territories.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.territories.'),
            ]),
        ];

        $groups['Utilisateurs'] = [
            new AdminMenuItem([
                'id' => 'users',
                'label' => 'Utilisateurs',
                'icon' => 'lucide:user',
                'route' => $this->getRouteWithContext('admin.users.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.users.'),
            ]),
            new AdminMenuItem([
                'id' => 'roles',
                'label' => 'Rôles & Permissions',
                'icon' => 'lucide:shield-check',
                'route' => $this->getRouteWithContext('admin.roles.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.roles.'),
            ]),
            new AdminMenuItem([
                'id' => 'school-years',
                'label' => 'Années scolaires',
                'icon' => 'lucide:calendar-days',
                'route' => $this->getRouteWithContext('admin.school-years.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.school-years.'),
            ]),
            new AdminMenuItem([
                'id' => 'classrooms',
                'label' => 'Classes',
                'icon' => 'lucide:layers',
                'route' => $this->getRouteWithContext('admin.classrooms.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.classrooms.'),
            ]),
            new AdminMenuItem([
                'id' => 'academic-levels',
                'label' => 'Niveaux',
                'icon' => 'lucide:layers-3',
                'route' => $this->getRouteWithContext('admin.academic-levels.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.academic-levels.'),
            ]),
            new AdminMenuItem([
                'id' => 'semesters',
                'label' => 'Semestres',
                'icon' => 'lucide:calendar-range',
                'route' => $this->getRouteWithContext('admin.semesters.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.semesters.'),
            ]),
            new AdminMenuItem([
                'id' => 'mois',
                'label' => 'Mois',
                'icon' => 'lucide:calendar-days',
                'route' => $this->getRouteWithContext('admin.mois.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.mois.'),
            ]),
            new AdminMenuItem([
                'id' => 'cycles',
                'label' => 'Cycles',
                'icon' => 'lucide:repeat',
                'route' => $this->getRouteWithContext('admin.cycles.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.cycles.'),
            ]),
            new AdminMenuItem([
                'id' => 'filieres',
                'label' => 'Filières',
                'icon' => 'lucide:git-branch',
                'route' => $this->getRouteWithContext('admin.filiaires.index'),
                'active' => $this->isCurrentRoutePrefixed('admin.filiaires.'),
            ]),
        ];

        // Menus contextuels : école sélectionnée ou rôles multi-écoles sans école fixe
        $hasSchoolContext = $selectedSchoolId
            || ($user && $user->hasAnyRole(['super-admin', 'admin-sous-division']));

        if ($hasSchoolContext) {
            // Groupe Mois (stock = groupe dédié)
            $groups['Gestion'] = [
                new AdminMenuItem([
                    'id' => 'mois',
                    'label' => 'Mois',
                    'icon' => 'lucide:calendar-days',
                    'route' => $this->getRouteWithContext('admin.mois.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.mois.'),
                ]),
            ];

            $groups['Stock'] = [
                new AdminMenuItem([
                    'id' => 'stock-articles',
                    'label' => 'Articles',
                    'icon' => 'lucide:package',
                    'route' => $this->getRouteWithContext('admin.stock-articles.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.stock-articles.'),
                ]),
                new AdminMenuItem([
                    'id' => 'stock-categories',
                    'label' => 'Catégories',
                    'icon' => 'lucide:tag',
                    'route' => $this->getRouteWithContext('admin.stock-categories.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.stock-categories.'),
                ]),
                new AdminMenuItem([
                    'id' => 'stock-providers',
                    'label' => 'Fournisseurs',
                    'icon' => 'lucide:truck',
                    'route' => $this->getRouteWithContext('admin.stock-providers.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.stock-providers.'),
                ]),
                new AdminMenuItem([
                    'id' => 'stock-entries',
                    'label' => 'Entrées',
                    'icon' => 'lucide:arrow-down-circle',
                    'route' => $this->getRouteWithContext('admin.stock-entries.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.stock-entries.'),
                ]),
                new AdminMenuItem([
                    'id' => 'stock-exits',
                    'label' => 'Sorties',
                    'icon' => 'lucide:arrow-up-circle',
                    'route' => $this->getRouteWithContext('admin.stock-exits.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.stock-exits.'),
                ]),
                new AdminMenuItem([
                    'id' => 'stock-states',
                    'label' => 'États',
                    'icon' => 'lucide:check-square',
                    'route' => $this->getRouteWithContext('admin.stock-states.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.stock-states.'),
                ]),
                new AdminMenuItem([
                    'id' => 'stock-inventories',
                    'label' => 'Inventaires',
                    'icon' => 'lucide:clipboard-list',
                    'route' => $this->getRouteWithContext('admin.stock-inventories.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.stock-inventories.'),
                ]),
            ];

            $groups['Infrastructures'] = [
                new AdminMenuItem([
                    'id' => 'infra-dashboard',
                    'label' => 'Tableau de bord',
                    'icon' => 'lucide:layout-dashboard',
                    'route' => $this->getRouteWithContext('admin.infra.dashboard'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra.dashboard'),
                ]),
                new AdminMenuItem([
                    'id' => 'infra-categories',
                    'label' => 'Catégories',
                    'icon' => 'lucide:tag',
                    'route' => $this->getRouteWithContext('admin.infra-categories.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra-categories.'),
                ]),
                new AdminMenuItem([
                    'id' => 'infra-bailleurs',
                    'label' => 'Bailleurs',
                    'icon' => 'lucide:handshake',
                    'route' => $this->getRouteWithContext('admin.infra-bailleurs.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra-bailleurs.'),
                ]),
                new AdminMenuItem([
                    'id' => 'infra-infrastructures',
                    'label' => 'Bâtiments & ouvrages',
                    'icon' => 'lucide:building-2',
                    'route' => $this->getRouteWithContext('admin.infra-infrastructures.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra-infrastructures.'),
                ]),
                new AdminMenuItem([
                    'id' => 'infra-infrastructure-inventaires',
                    'label' => 'Suivi bâtiments',
                    'icon' => 'lucide:clipboard-check',
                    'route' => $this->getRouteWithContext('admin.infra-infrastructure-inventaires.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra-infrastructure-inventaires.'),
                ]),
                new AdminMenuItem([
                    'id' => 'infra-equipements',
                    'label' => 'Équipements',
                    'icon' => 'lucide:cpu',
                    'route' => $this->getRouteWithContext('admin.infra-equipements.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra-equipements.'),
                ]),
                new AdminMenuItem([
                    'id' => 'infra-inventaires',
                    'label' => 'Suivi équipements',
                    'icon' => 'lucide:clipboard-list',
                    'route' => $this->getRouteWithContext('admin.infra-inventaires.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra-inventaires.'),
                ]),
                new AdminMenuItem([
                    'id' => 'infra-etats',
                    'label' => 'Signalements',
                    'icon' => 'lucide:alert-triangle',
                    'route' => $this->getRouteWithContext('admin.infra-etats.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.infra-etats.'),
                ]),
            ];

            $groups['Écoles'] = [
                new AdminMenuItem([
                    'id' => 'schools',
                    'label' => 'Écoles',
                    'icon' => 'mdi:school',
                    'route' => $this->getRouteWithContext('admin.schools.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.schools.')
                        || $this->isCurrentRoutePrefixed('admin.filiaires.')
                        || $this->isCurrentRoutePrefixed('admin.cycles.')
                        || $this->isCurrentRoutePrefixed('admin.academic-levels.')
                        || $this->isCurrentRoutePrefixed('admin.classrooms.'),
                    'children' => [
                        new AdminMenuItem([
                            'id' => 'filieres',
                            'label' => 'Filières',
                            'icon' => 'lucide:git-branch',
                            'route' => $this->getRouteWithContext('admin.filiaires.index'),
                            'active' => $this->isCurrentRoutePrefixed('admin.filiaires.'),
                        ]),
                        new AdminMenuItem([
                            'id' => 'academic-levels',
                            'label' => 'Niveaux',
                            'icon' => 'lucide:layers-3',
                            'route' => $this->getRouteWithContext('admin.academic-levels.index'),
                            'active' => $this->isCurrentRoutePrefixed('admin.academic-levels.'),
                        ]),
                        new AdminMenuItem([
                            'id' => 'cycles',
                            'label' => 'Cycles',
                            'icon' => 'lucide:repeat',
                            'route' => $this->getRouteWithContext('admin.cycles.index'),
                            'active' => $this->isCurrentRoutePrefixed('admin.cycles.'),
                        ]),
                        new AdminMenuItem([
                            'id' => 'classrooms',
                            'label' => 'Classes',
                            'icon' => 'lucide:layers',
                            'route' => $this->getRouteWithContext('admin.classrooms.index'),
                            'active' => $this->isCurrentRoutePrefixed('admin.classrooms.'),
                        ]),
                    ],
                ]),
            ];
            $groups['Élèves'] = [
                new AdminMenuItem([
                    'id' => 'students-overview',
                    'label' => 'Vue d\'ensemble',
                    'icon' => 'lucide:layout-dashboard',
                    'route' => $this->getRouteWithContext('admin.students.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.students.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-inscriptions',
                    'label' => 'Inscriptions',
                    'icon' => 'lucide:user-plus',
                    'route' => $this->getRouteWithContext('admin.registrations.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.registrations.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-presences',
                    'label' => 'Présences',
                    'icon' => 'lucide:calendar-check',
                    'route' => $this->getRouteWithContext('admin.presences.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.presences.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-visits',
                    'label' => 'Visites de classe',
                    'icon' => 'lucide:eye',
                    'route' => $this->getRouteWithContext('admin.visits.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.visits.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-fiche-cotation',
                    'label' => 'Fiches de cotation',
                    'icon' => 'lucide:clipboard-list',
                    'route' => $this->getRouteWithContext('admin.fiche-cotations.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.fiche-cotations.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-deliberations',
                    'label' => 'Délibérations',
                    'icon' => 'lucide:scale',
                    'route' => $this->getRouteWithContext('admin.deliberations.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.deliberations.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-repechages',
                    'label' => 'Repéchage',
                    'icon' => 'lucide:refresh-cw',
                    'route' => $this->getRouteWithContext('admin.repechages.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.repechages.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-validation-aureats',
                    'label' => 'Validation lauréats',
                    'icon' => 'lucide:award',
                    'route' => $this->getRouteWithContext('admin.validation-aureats.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.validation-aureats.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-indiscipline',
                    'label' => 'Gestion disciplinaire',
                    'icon' => 'lucide:shield-alert',
                    'route' => $this->getRouteWithContext('admin.indiscipline.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.indiscipline.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-bulletins',
                    'label' => 'Bulletin scolaire',
                    'icon' => 'lucide:file-text',
                    'route' => $this->getRouteWithContext('admin.bulletins.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.bulletins.'),
                ]),
                new AdminMenuItem([
                    'id' => 'students-exits',
                    'label' => 'Sorties de classe',
                    'icon' => 'lucide:log-out',
                    'route' => $this->getRouteWithContext('admin.student-exits.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.student-exits.'),
                ]),
            ];

            $groups['Ressources Humaines'] = [
                new AdminMenuItem([
                    'id' => 'rh-personnels',
                    'label' => 'Personnels',
                    'icon' => 'lucide:id-card',
                    'route' => $this->getRouteWithContext('admin.personnels.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.personnels.'),
                ]),
                new AdminMenuItem([
                    'id' => 'rh-roles',
                    'label' => 'Rôles & assignation',
                    'icon' => 'lucide:shield-check',
                    'route' => $this->getRouteWithContext('admin.roles.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.roles.'),
                ]),
                new AdminMenuItem([
                    'id' => 'rh-personnel-presences',
                    'label' => 'Présences personnel',
                    'icon' => 'lucide:calendar-check-2',
                    'route' => $this->getRouteWithContext('admin.personnel-presences.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.personnel-presences.'),
                ]),
                new AdminMenuItem([
                    'id' => 'rh-affectations',
                    'label' => 'Affectations',
                    'icon' => 'lucide:map-pin',
                    'route' => $this->getRouteWithContext('admin.person-affectations.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.person-affectations.'),
                ]),
            ];

            // Groupe Comptabilité — entrées plates (comme Infrastructure / RH)
            $groups['Comptabilité'] = [
                new AdminMenuItem([
                    'id' => 'accounting-dashboard',
                    'label' => 'Tableau de bord',
                    'icon' => 'lucide:layout-dashboard',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'dashboard']),
                    'active' => $this->isAccountingSection('dashboard'),
                ]),
                new AdminMenuItem([
                    'id' => 'account-plans',
                    'label' => 'Plan comptable',
                    'icon' => 'lucide:list-ordered',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'account-plans']),
                    'active' => $this->isAccountingSection('account-plans'),
                ]),
                new AdminMenuItem([
                    'id' => 'sub-account-plans',
                    'label' => 'Sous-comptes',
                    'icon' => 'lucide:list-tree',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'sub-account-plans']),
                    'active' => $this->isAccountingSection('sub-account-plans'),
                ]),
                new AdminMenuItem([
                    'id' => 'accounting-journal',
                    'label' => 'Journal',
                    'icon' => 'lucide:file-edit',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'journal']),
                    'active' => $this->isAccountingSection('journal'),
                ]),
                new AdminMenuItem([
                    'id' => 'fees',
                    'label' => 'Frais & produits',
                    'icon' => 'lucide:receipt',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'fees']),
                    'active' => $this->isAccountingSection('fees'),
                ]),
                new AdminMenuItem([
                    'id' => 'currencies',
                    'label' => 'Monnaies & taux',
                    'icon' => 'lucide:coins',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'currencies']),
                    'active' => $this->isAccountingSection('currencies'),
                ]),
                new AdminMenuItem([
                    'id' => 'payments',
                    'label' => 'Paiements',
                    'icon' => 'lucide:credit-card',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'payments']),
                    'active' => $this->isAccountingSection('payments'),
                ]),
                new AdminMenuItem([
                    'id' => 'accounting-reports',
                    'label' => 'États & rapports',
                    'icon' => 'lucide:bar-chart-big',
                    'route' => $this->getRouteWithContext('admin.accounting.index', ['section' => 'reports']),
                    'active' => $this->isAccountingSection('reports'),
                ]),
            ];

            // Groupe Pédagogie — planification des cours et activités scolaires
            $groups['Pédagogie'] = [
                new AdminMenuItem([
                    'id' => 'planning',
                    'label' => 'Planification des cours',
                    'icon' => 'lucide:calendar-clock',
                    'route' => $this->getRouteWithContext('admin.planning.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.planning.'),
                ]),
                new AdminMenuItem([
                    'id' => 'activities',
                    'label' => 'Activités scolaires',
                    'icon' => 'lucide:party-popper',
                    'route' => $this->getRouteWithContext('admin.activities.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.activities.'),
                ]),
            ];

            // Groupe Année scolaire — création/activation année, semestres, périodes
            $groups['Année scolaire'] = [
                new AdminMenuItem([
                    'id' => 'school-year',
                    'label' => 'Année scolaire',
                    'icon' => 'lucide:calendar-days',
                    'route' => $this->getRouteWithContext('admin.school-years.index'),
                    'active' => $this->isCurrentRoutePrefixed('admin.school-years.'),
                    'children' => [
                        new AdminMenuItem([
                            'id' => 'school-year-create',
                            'label' => 'Créer une année',
                            'icon' => 'lucide:plus-circle',
                            'route' => $this->getRouteWithContext('admin.school-years.create'),
                            'active' => $this->isCurrentRoutePrefixed('admin.school-years.create'),
                        ]),
                        new AdminMenuItem([
                            'id' => 'school-year-activate',
                            'label' => 'Activer une année',
                            'icon' => 'lucide:check-circle',
                            'route' => $this->getRouteWithContext('admin.school-years.activate', ['id' => \App\Models\SchoolYear::first()?->id ?? 1]),
                            'active' => $this->isCurrentRoutePrefixed('admin.school-years.activate'),
                        ]),
                        new AdminMenuItem([
                            'id' => 'semesters',
                            'label' => 'Semestres',
                            'icon' => 'lucide:calendar-range',
                            'route' => $this->getRouteWithContext('admin.semesters.index'),
                            'active' => $this->isCurrentRoutePrefixed('admin.semesters.'),
                        ]),
                        new AdminMenuItem([
                            'id' => 'periods',
                            'label' => 'Périodes',
                            'icon' => 'lucide:clock',
                            'route' => $this->getRouteWithContext('admin.periods.index'),
                            'active' => $this->isCurrentRoutePrefixed('admin.periods.'),
                        ]),
                    ],
                ]),
            ];
        }

        $activeModule = $this->resolveActiveModuleGroup();
        $restrictGestionRootId = $activeModule === 'Gestion' ? 'mois' : null;

        if ($activeModule !== null) {
            $groups = array_intersect_key($groups, array_flip([$activeModule]));
            unset($groups['Dashboard']);
        }

        if ($restrictGestionRootId !== null && isset($groups['Gestion'])) {
            $groups['Gestion'] = array_values(array_filter(
                $groups['Gestion'],
                fn (AdminMenuItem $item) => $item->id === $restrictGestionRootId
            ));
        }

        return $groups;
    }

    /**
     * Tableau de bord : pas de sidebar module.
     */
    public function isDashboard(): bool
    {
        return Request::route()?->getName() === 'admin.dashboard';
    }

    /**
     * Sidebar visible uniquement dans un module reconnu (pas sur le dashboard).
     */
    public function shouldShowModuleSidebar(): bool
    {
        $route = Request::route()?->getName() ?? '';
        if (! str_starts_with($route, 'admin.')) {
            return false;
        }

        $user = auth()->user();
        if ($user && $user->hasRole('admin-proved') && ! $user->hasRole('super-admin')) {
            return true;
        }

        return ! $this->isDashboard()
            && $this->resolveActiveModuleGroup() !== null;
    }

    /**
     * Groupe de menu actif selon la route (null = hors module / dashboard).
     */
    public function resolveActiveModuleGroup(?string $route = null): ?string
    {
        $current = $route ?? Request::route()?->getName() ?? '';

        if ($current === '' || $current === 'admin.dashboard') {
            return null;
        }

        $user = auth()->user();
        $isProvedOnly = $user && $user->hasRole('admin-proved') && ! $user->hasRole('super-admin');

        // Pour le PROVED, les écoles restent dans Organisation (pas le module Écoles)
        if ($isProvedOnly && str_starts_with($current, 'admin.schools.')) {
            return 'Organisation';
        }

        if (
            str_starts_with($current, 'admin.students.') ||
            str_starts_with($current, 'admin.presences.') ||
            str_starts_with($current, 'admin.fiche-cotations.') ||
            str_starts_with($current, 'admin.deliberations.') ||
            str_starts_with($current, 'admin.repechages.') ||
            str_starts_with($current, 'admin.validation-aureats.') ||
            str_starts_with($current, 'admin.indiscipline.') ||
            str_starts_with($current, 'admin.bulletins.') ||
            str_starts_with($current, 'admin.student-exits.') ||
            str_starts_with($current, 'admin.visits.') ||
            str_starts_with($current, 'admin.registrations.')
        ) {
            return 'Élèves';
        }

        if (
            str_starts_with($current, 'admin.personnels.') ||
            str_starts_with($current, 'admin.roles.') ||
            str_starts_with($current, 'admin.personnel-presences.') ||
            str_starts_with($current, 'admin.person-affectations.')
        ) {
            return 'Ressources Humaines';
        }

        if (
            str_starts_with($current, 'admin.schools.') ||
            str_starts_with($current, 'admin.filiaires.') ||
            str_starts_with($current, 'admin.cycles.') ||
            str_starts_with($current, 'admin.academic-levels.') ||
            str_starts_with($current, 'admin.classrooms.') ||
            str_starts_with($current, 'admin.types.')
        ) {
            return 'Écoles';
        }

        if (str_starts_with($current, 'admin.accounting.')) {
            return 'Comptabilité';
        }

        if (
            str_starts_with($current, 'admin.planning.') ||
            str_starts_with($current, 'admin.activities.')
        ) {
            return 'Pédagogie';
        }

        if (
            str_starts_with($current, 'admin.school-years.') ||
            str_starts_with($current, 'admin.semesters.') ||
            str_starts_with($current, 'admin.periods.')
        ) {
            return 'Année scolaire';
        }

        if (
            str_starts_with($current, 'admin.infra.dashboard') ||
            str_starts_with($current, 'admin.infra-')
        ) {
            return 'Infrastructures';
        }

        if (str_starts_with($current, 'admin.stock-')) {
            return 'Stock';
        }

        if (str_starts_with($current, 'admin.mois.')) {
            return 'Gestion';
        }

        if (str_starts_with($current, 'admin.users.')) {
            return 'Utilisateurs';
        }

        if (
            str_starts_with($current, 'admin.countries.') ||
            str_starts_with($current, 'admin.provinces.') ||
            str_starts_with($current, 'admin.communes.') ||
            str_starts_with($current, 'admin.territories.')
        ) {
            return 'Administration';
        }

        if (
            str_starts_with($current, 'admin.proveds.') ||
            str_starts_with($current, 'admin.sous-divisions.') ||
            str_starts_with($current, 'admin.sous-division.')
        ) {
            return 'Organisation';
        }

        if (str_starts_with($current, 'admin.collecte-rapides.')) {
            return 'Collecte rapide';
        }

        return null;
    }

    /**
     * @return array{label: string, icon: string, url: string}|null
     */
    public function getModuleMeta(): ?array
    {
        $group = $this->resolveActiveModuleGroup();
        if ($group === null) {
            return null;
        }

        $definitions = [
            'Administration' => ['icon' => 'lucide:globe-2', 'url' => 'admin.countries.index'],
            'Organisation' => ['icon' => 'mdi:school', 'url' => 'admin.schools.index'],
            'Collecte rapide' => ['icon' => 'lucide:clipboard-list', 'url' => 'admin.collecte-rapides.index'],
            'Écoles' => ['icon' => 'mdi:school', 'url' => 'admin.schools.index'],
            'Élèves' => ['icon' => 'lucide:graduation-cap', 'url' => 'admin.students.index'],
            'Ressources Humaines' => ['icon' => 'lucide:users', 'url' => 'admin.personnels.index'],
            'Comptabilité' => ['icon' => 'lucide:calculator', 'url' => 'admin.accounting.index'],
            'Pédagogie' => ['icon' => 'lucide:book-open', 'url' => 'admin.planning.index'],
            'Année scolaire' => ['icon' => 'lucide:calendar-days', 'url' => 'admin.school-years.index'],
            'Infrastructures' => ['icon' => 'lucide:building-2', 'url' => 'admin.infra.dashboard'],
            'Stock' => ['icon' => 'lucide:package', 'url' => 'admin.stock-articles.index'],
            'Gestion' => ['icon' => 'lucide:calendar-days', 'url' => 'admin.mois.index'],
            'Utilisateurs' => ['icon' => 'lucide:user-cog', 'url' => 'admin.users.index'],
        ];

        $def = $definitions[$group] ?? ['icon' => 'lucide:layout-grid', 'url' => 'admin.dashboard'];

        try {
            $url = route($def['url']);
        } catch (Exception) {
            $url = route('admin.dashboard');
        }

        return [
            'label' => $group,
            'icon' => $def['icon'],
            'url' => $url,
        ];
    }

    /**
     * Liens du fil d'Ariane (module + pages intermédiaires).
     *
     * @param  array<int, array{label: string, url: string}>  $extras
     * @return array{links: array<int, array{label: string, url: string}>, current: string, backUrl: string, backLabel: string}
     */
    public function buildBreadcrumb(array $extras = [], ?string $current = null): array
    {
        $links = [];
        $meta = $this->getModuleMeta();
        if ($meta !== null) {
            $links[] = ['label' => $meta['label'], 'url' => $meta['url']];
        }

        foreach ($extras as $extra) {
            $links[] = $extra;
        }

        return [
            'links' => $links,
            'current' => $current ?? '',
            'backUrl' => route('admin.dashboard'),
            'backLabel' => 'Tableau de bord',
        ];
    }

    public function shouldShowSchoolContextSwitchers(): bool
    {
        $group = $this->resolveActiveModuleGroup();

        return $group !== null && ! in_array($group, ['Administration', 'Organisation', 'Utilisateurs', 'Collecte rapide'], true);
    }

    public function render(array $items): string
    {
        $html = '';
        $user = auth()->user();
        $selectedSchoolId = session('selected_school_id');
        foreach ($items as $item) {
            // Si l'item concerne une école et le super admin n'a pas sélectionné d'école, on masque
            if (isset($item->requiresSchool) && $item->requiresSchool === true) {
                if ($user && $user->hasAnyRole(['super-admin', 'admin-proved', 'admin-sous-division']) && ! $selectedSchoolId) {
                    continue;
                }
            }
            $html .= view('backend.layouts.partials.sidebar.menu-item', ['item' => $item])->render();
        }

        return $html;
    }

    public function shouldExpandSubmenu(AdminMenuItem $item): bool
    {
        if (empty($item->children)) {
            return false;
        }
        foreach ($item->children as $child) {
            if ($child->active) {
                return true;
            }
        }

        return false;
    }

    private function getRouteWithContext(string $name, array $params = []): string
    {
        $selectedSchoolId = session('selected_school_id');
        if ($selectedSchoolId && ! isset($params['school_id'])) {
            $params['school_id'] = $selectedSchoolId;
        }

        try {
            return route($name, $params);
        } catch (Exception $e) {
            return '#';
        }
    }

    private function isCurrentRoutePrefixed(string $prefix): bool
    {
        $current = Request::route()?->getName();

        return $current !== null && str_starts_with($current, $prefix);
    }

    /**
     * Rubrique active du module Comptabilité (query ?section= sur admin.accounting.index).
     */
    private function isAccountingSection(string $section): bool
    {
        $name = Request::route()?->getName() ?? '';
        if (! str_starts_with($name, 'admin.accounting')) {
            return false;
        }
        if ($name === 'admin.accounting.index') {
            return request()->query('section', 'dashboard') === $section;
        }

        return $section === 'dashboard';
    }
}

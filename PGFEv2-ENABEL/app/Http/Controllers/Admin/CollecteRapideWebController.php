<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportCollecteRapideRequest;
use App\Http\Requests\StoreCollecteRapideRequest;
use App\Http\Requests\UpdateCollecteRapideStepRequest;
use App\Models\CollecteRapide;
use App\Models\Proved;
use App\Models\SchoolYear;
use App\Services\Collecte\CollecteRapideExcelService;
use App\Services\Collecte\CollecteRapideQueryService;
use App\Services\Collecte\CollecteRapideSchema;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CollecteRapideWebController extends Controller
{
    public function __construct(
        private readonly CollecteRapideSchema $schema,
        private readonly CollecteRapideExcelService $excel,
        private readonly CollecteRapideQueryService $queries,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CollecteRapide::class);

        $user = $request->user();
        $query = $this->queries->applyIndexFilters(
            $this->queries->baseQueryForUser($user),
            $request,
        );

        $collectes = $query->paginate(20)->appends($request->query());
        $schoolYears = SchoolYear::query()->orderByDesc('name')->get(['id', 'name']);
        $sousDivisions = $this->queries->sousDivisionsForUser($user);
        $steps = $this->schema->steps();
        $stats = $this->queries->statsForUser($user, $sousDivisions->count());

        return view('backend.pages.collecte-rapides.index', compact(
            'collectes',
            'schoolYears',
            'sousDivisions',
            'steps',
            'stats',
        ));
    }

    public function store(StoreCollecteRapideRequest $request)
    {
        $this->authorize('create', CollecteRapide::class);

        $exists = CollecteRapide::query()
            ->where('sous_division_id', (int) $request->input('sous_division_id'))
            ->where('school_year_id', (int) $request->input('school_year_id'))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'sous_division_id' => 'Une collecte existe déjà pour cette sous-division et cette année scolaire.',
            ]);
        }

        $collecte = CollecteRapide::create([
            'proved_id' => $request->provedId(),
            'sous_division_id' => (int) $request->input('sous_division_id'),
            'school_year_id' => (int) $request->input('school_year_id'),
            'status' => CollecteRapide::STATUS_DRAFT,
            'current_step' => 1,
            'data' => $this->schema->emptyPayload(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.collecte-rapides.step', [$collecte, 1])
            ->with('success', 'Collecte créée. Remplissez les tableaux étape par étape.');
    }

    public function step(Request $request, CollecteRapide $collecte_rapide, int $step)
    {
        $this->authorize('view', $collecte_rapide);

        $last = $this->schema->lastStep();
        if ($step < 0 || $step > $last) {
            abort(404);
        }

        $meta = $this->schema->step($step);
        if ($meta === null) {
            abort(404);
        }

        $collecte = $collecte_rapide->load(['sousDivision.proved.province', 'schoolYear', 'proved.province']);
        $readonly = $collecte->isSubmitted();
        $regimes = $this->schema->regimes();
        $teachingTypes = $this->schema->teachingTypes();
        $steps = $this->schema->steps();
        $payload = array_replace_recursive($this->schema->emptyPayload(), $collecte->data ?? []);

        return view('backend.pages.collecte-rapides.wizard', [
            'collecte' => $collecte,
            'step' => $step,
            'meta' => $meta,
            'steps' => $steps,
            'regimes' => $regimes,
            'teachingTypes' => $teachingTypes,
            'payload' => $payload,
            'readonly' => $readonly,
            'lastStep' => $last,
        ]);
    }

    public function updateStep(UpdateCollecteRapideStepRequest $request, CollecteRapide $collecte_rapide, int $step)
    {
        $this->authorize('update', $collecte_rapide);

        if ($this->schema->step($step) === null) {
            abort(404);
        }

        if ($step === 0 || $step === $this->schema->lastStep()) {
            if ($request->shouldAdvance() && $step < $this->schema->lastStep()) {
                $collecte_rapide->update([
                    'current_step' => max($collecte_rapide->current_step, $step + 1),
                ]);
            }

            $next = $request->shouldAdvance() ? min($step + 1, $this->schema->lastStep()) : $step;

            return redirect()
                ->route('admin.collecte-rapides.step', [$collecte_rapide, $next])
                ->with('success', 'Étape enregistrée.');
        }

        $data = $this->schema->mergeStepData(
            $collecte_rapide->data ?? $this->schema->emptyPayload(),
            $step,
            $request->input('data', [])
        );

        $updates = ['data' => $data];
        if ($request->shouldAdvance()) {
            $updates['current_step'] = max($collecte_rapide->current_step, min($step + 1, $this->schema->lastStep()));
        } else {
            $updates['current_step'] = max($collecte_rapide->current_step, $step);
        }

        $collecte_rapide->update($updates);

        $next = $request->shouldAdvance() ? min($step + 1, $this->schema->lastStep()) : $step;

        return redirect()
            ->route('admin.collecte-rapides.step', [$collecte_rapide, $next])
            ->with('success', $request->shouldAdvance() ? 'Étape enregistrée.' : 'Brouillon sauvegardé.');
    }

    public function submit(Request $request, CollecteRapide $collecte_rapide)
    {
        $this->authorize('submit', $collecte_rapide);

        $collecte_rapide->update([
            'status' => CollecteRapide::STATUS_SUBMITTED,
            'current_step' => $this->schema->lastStep(),
        ]);

        return redirect()
            ->route('admin.collecte-rapides.index')
            ->with('success', 'Collecte soumise. Elle apparaît dans la synthèse PROVED.');
    }

    public function reopen(Request $request, CollecteRapide $collecte_rapide)
    {
        $this->authorize('reopen', $collecte_rapide);

        $collecte_rapide->update([
            'status' => CollecteRapide::STATUS_DRAFT,
        ]);

        return redirect()
            ->route('admin.collecte-rapides.step', [$collecte_rapide, max(1, $collecte_rapide->current_step)])
            ->with('success', 'Collecte rouverte en brouillon.');
    }

    public function destroy(CollecteRapide $collecte_rapide)
    {
        $this->authorize('delete', $collecte_rapide);
        $collecte_rapide->delete();

        return redirect()
            ->route('admin.collecte-rapides.index')
            ->with('success', 'Collecte supprimée.');
    }

    public function synthese(Request $request)
    {
        $this->authorize('viewAny', CollecteRapide::class);

        $user = $request->user();
        $provedId = (int) $user->proved_id;

        $schoolYears = SchoolYear::query()->orderByDesc('name')->get(['id', 'name', 'is_active']);
        $schoolYearId = (int) ($request->input('school_year_id') ?: ($schoolYears->firstWhere('is_active', true)?->id ?? $schoolYears->first()?->id ?? 0));

        $collectes = $this->queries->submittedForSynthese($provedId, $schoolYearId);
        $payload = $this->schema->aggregate($collectes->pluck('data')->all());
        $proved = Proved::query()->with('province')->find($provedId);
        $steps = $this->schema->steps();
        $regimes = $this->schema->regimes();
        $teachingTypes = $this->schema->teachingTypes();

        return view('backend.pages.collecte-rapides.synthese', compact(
            'collectes',
            'payload',
            'schoolYears',
            'schoolYearId',
            'provedId',
            'proved',
            'steps',
            'regimes',
            'teachingTypes',
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', CollecteRapide::class);

        $provedId = (int) $request->user()->proved_id;
        $collectes = $this->queries->forExport($provedId, $request);
        $yearLabel = $collectes->first()?->schoolYear?->name ?? 'export';
        $filename = 'collecte-rapide-'.$this->slug($yearLabel).'-'.now()->format('Ymd-His').'.xlsx';

        return $this->excel->download($collectes, $filename);
    }

    public function exportOne(CollecteRapide $collecte_rapide): StreamedResponse
    {
        $this->authorize('view', $collecte_rapide);

        $collecte_rapide->load(['sousDivision', 'proved.province', 'schoolYear']);
        $filename = 'collecte-'.$this->slug($collecte_rapide->sousDivision?->name ?? 'sd')
            .'-'.$this->slug($collecte_rapide->schoolYear?->name ?? 'annee').'.xlsx';

        return $this->excel->download(collect([$collecte_rapide]), $filename);
    }

    public function exportSynthese(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', CollecteRapide::class);

        $provedId = (int) $request->user()->proved_id;
        $schoolYearId = (int) $request->input('school_year_id');

        $collectes = $this->queries->submittedForExport($provedId, $schoolYearId);
        $proved = Proved::query()->with('province')->find($provedId);
        $yearName = $collectes->first()?->schoolYear?->name
            ?? SchoolYear::query()->find($schoolYearId)?->name
            ?? 'annee';

        $synthese = [
            'sous_divisions' => $collectes->pluck('sousDivision.name')->filter()->implode(', '),
            'proved' => $proved?->name ?? '',
            'province' => $proved?->province?->name ?? '',
            'school_year' => $yearName,
            'payload' => $this->schema->aggregate($collectes->pluck('data')->all()),
        ];

        $filename = 'synthese-collecte-'.$this->slug($yearName).'-'.now()->format('Ymd-His').'.xlsx';

        return $this->excel->downloadWithSynthese($collectes, $synthese, $filename);
    }

    public function import(ImportCollecteRapideRequest $request)
    {
        $this->authorize('create', CollecteRapide::class);

        $user = $request->user();
        $result = $this->excel->import(
            $request->file('file'),
            (int) $user->proved_id,
            (int) $request->input('school_year_id'),
            (int) $user->id,
        );

        $message = sprintf(
            'Import terminé : %d feuille(s) — %d créée(s), %d mise(s) à jour.',
            $result['imported'],
            $result['created'],
            $result['updated'],
        );

        if ($result['skipped'] !== []) {
            $message .= ' Ignorées : '.implode(' ', $result['skipped']);
        }

        return redirect()
            ->route('admin.collecte-rapides.index', ['school_year_id' => $request->input('school_year_id')])
            ->with('success', $message)
            ->with('import_warnings', $result['warnings']);
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $value) ?? 'x';

        return trim(mb_strtolower($slug), '-_') ?: 'x';
    }
}

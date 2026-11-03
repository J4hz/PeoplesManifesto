<?php

use App\Models\Area;
use App\Services\ClusterRanker;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => pilot());

function ranker(): ClusterRanker
{
    return app(ClusterRanker::class);
}

it('ranks by distinct residents, not by the number of reports', function () {
    $resident = citizen();
    $oneVoiceManyReports = clusterWithReporters([$resident, $resident, $resident, $resident]);
    $fourVoices = clusterWithReporters([citizen(), citizen(), citizen(), citizen()]);

    ranker()->recalculate($oneVoiceManyReports);
    ranker()->recalculate($fourVoices);

    expect($oneVoiceManyReports->resident_count)->toBe(1)
        ->and($oneVoiceManyReports->submission_count)->toBe(4)
        ->and($fourVoices->resident_count)->toBe(4)
        ->and($fourVoices->score)->toBeGreaterThan($oneVoiceManyReports->score);
});

it('weighs urgent water, health and security above other urgent issues', function () {
    $r = ranker();
    $base = fn (string $severity, string $category) => $r->score(3, $severity, $category, 1, 4, 1);

    expect($base('urgent', 'water'))->toBeGreaterThan($base('urgent', 'roads'))
        ->and($base('urgent', 'health'))->toBeGreaterThan($base('urgent', 'markets'))
        ->and($base('urgent', 'roads'))->toBeGreaterThan($base('serious', 'roads'))
        ->and($base('serious', 'roads'))->toBeGreaterThan($base('annoying', 'roads'))
        ->and($base('serious', 'water'))->toBe($base('serious', 'roads'));
});

it('rewards spread across areas and recent growth', function () {
    $r = ranker();

    expect($r->score(3, 'serious', 'roads', 3, 4, 1))->toBeGreaterThan($r->score(3, 'serious', 'roads', 1, 4, 1))
        ->and($r->score(3, 'serious', 'roads', 1, 4, 2.5))->toBeGreaterThan($r->score(3, 'serious', 'roads', 1, 4, 0.5));
});

it('reads the weights from config so they can be tuned without code changes', function () {
    $before = ranker()->score(5, 'serious', 'roads', 1, 4, 1);
    config(['ranking.weights.residents' => 10.0]);

    expect(ranker()->score(5, 'serious', 'roads', 1, 4, 1))->toBeGreaterThan($before);
});

it('counts spread from the areas residents reported from', function () {
    $ward = ward();
    $areas = collect(['Zone A', 'Zone B', 'Zone C', 'Zone D'])->map(fn ($n) => Area::create(['ward_id' => $ward->id, 'name' => $n]));
    $cluster = clusterWithReporters([citizen(), citizen(), citizen()]);
    $cluster->submissions()->get()->each(fn ($s, $i) => DB::table('submissions')->where('id', $s->id)->update(['area_id' => $areas[$i % 2]->id]));

    ranker()->recalculate($cluster);

    expect($cluster->areas_count)->toBe(2);
});

it('gives long-standing accounts a light boost', function () {
    $old = clusterWithReporters([citizen('kiuu', 400), citizen('kiuu', 400)]);
    $regular = clusterWithReporters([citizen('kiuu', 60), citizen('kiuu', 60)]);

    ranker()->recalculate($old);
    ranker()->recalculate($regular);

    expect($old->score)->toBeGreaterThan($regular->score);
});

it('discounts a burst of brand new accounts piling onto one issue', function () {
    $newAccounts = collect(range(1, 8))->map(fn () => citizen('kiuu', 1))->all();
    $established = collect(range(1, 8))->map(fn () => citizen('kiuu', 60))->all();

    $burst = clusterWithReporters($newAccounts);
    $organic = clusterWithReporters($established);

    expect(ranker()->weightedResidentCount($burst))->toBeLessThan(4.0);

    ranker()->recalculate($burst);
    ranker()->recalculate($organic);

    expect($burst->resident_count)->toBe(8)
        ->and($organic->score)->toBeGreaterThan($burst->score);
});

it('does not discount a few new accounts mixed with established ones', function () {
    $mixed = clusterWithReporters([citizen('kiuu', 1), citizen('kiuu', 1), citizen('kiuu', 60), citizen('kiuu', 60), citizen('kiuu', 60)]);

    expect(ranker()->weightedResidentCount($mixed))->toBe(5.0);
});

it('orders the ward list by score', function () {
    $small = clusterWithReporters([citizen()], ['title' => 'Small issue']);
    $big = clusterWithReporters([citizen(), citizen(), citizen()], ['title' => 'Big issue']);
    ranker()->recalculate($small);
    ranker()->recalculate($big);

    $this->actingAs(citizen(), 'citizen')->get('/my-ward')->assertOk()->assertSeeInOrder(['Big issue', 'Small issue']);
});

<?php

namespace App\Ai;

use App\Ai\Contracts\IntakeAnalyzer;
use App\Models\Submission;
use Illuminate\Support\Str;

/**
 * Used when no Anthropic key is configured, so the app works end to end on a
 * laptop. It is a keyword matcher, not a translator: the "translation" is the
 * original text.
 */
class LocalIntakeAnalyzer implements IntakeAnalyzer
{
    /** @var array<string, list<string>> */
    private const CATEGORY_WORDS = [
        'water' => ['water', 'maji', 'tap', 'borehole', 'mfereji', 'pipe', 'bomba', 'rationing'],
        'roads' => ['road', 'barabara', 'pothole', 'shimo', 'murram', 'bridge', 'daraja', 'footpath', 'street light'],
        'security' => ['security', 'usalama', 'thief', 'theft', 'wezi', 'mwizi', 'mugging', 'gang', 'police', 'polisi', 'robbery'],
        'health' => ['health', 'hospital', 'hospitali', 'dispensary', 'zahanati', 'clinic', 'drugs', 'dawa', 'nurse', 'daktari'],
        'schools' => ['school', 'shule', 'classroom', 'darasa', 'teacher', 'mwalimu', 'bursary', 'ecde'],
        'sanitation' => ['garbage', 'taka', 'takataka', 'dump', 'toilet', 'choo', 'drain', 'sewage', 'sewer'],
        'electricity' => ['electricity', 'stima', 'power', 'transformer', 'kplc', 'blackout'],
        'markets' => ['market', 'soko', 'stall', 'hawker', 'mama mboga', 'kibanda'],
        'environment' => ['noise', 'kelele', 'quarry', 'river', 'mto', 'flood', 'mafuriko', 'trees', 'miti'],
    ];

    private const URGENT_WORDS = ['urgent', 'haraka', 'emergency', 'dharura', 'died', 'dead', 'amekufa', 'fire', 'moto', 'outbreak', 'cholera', 'kipindupindu'];

    private const SERIOUS_WORDS = ['week', 'wiki', 'month', 'mwezi', 'days', 'siku', 'many', 'wengi', 'every', 'kila'];

    private const SWAHILI_WORDS = ['hakuna', 'maji', 'barabara', 'tafadhali', 'sisi', 'yetu', 'kwa', 'na', 'ya', 'wiki', 'siku', 'shida'];

    public function analyse(Submission $submission, array $wardNames, array $areaNames): IntakeResult
    {
        $text = (string) $submission->body;
        $lower = Str::lower($text);

        $category = 'other';
        $best = 0;
        foreach (self::CATEGORY_WORDS as $key => $words) {
            $hits = collect($words)->filter(fn ($w) => str_contains($lower, $w))->count();
            if ($hits > $best) {
                $best = $hits;
                $category = $key;
            }
        }

        $severity = match (true) {
            Str::contains($lower, self::URGENT_WORDS) => 'urgent',
            Str::contains($lower, self::SERIOUS_WORDS) => 'serious',
            default => 'annoying',
        };

        $words = preg_split('/\W+/u', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $swahiliHits = count(array_intersect($words, self::SWAHILI_WORDS));
        $language = $swahiliHits >= 2 ? 'sw' : 'en';

        $area = collect($areaNames)->first(fn ($name) => str_contains($lower, Str::lower($name))) ?? '';
        $ward = collect($wardNames)->first(fn ($name) => str_contains($lower, Str::lower($name))) ?? '';

        $clean = trim(preg_replace('/\s+/', ' ', $text));
        $title = Str::of($clean)->words(10, '')->limit(80, '')->rtrim('.,;: ')->ucfirst()->toString();

        return IntakeSchema::validate([
            'language' => $language,
            'translation_en' => Str::limit($clean, 2900, ''),
            'category' => $category,
            'severity' => $severity,
            'ward' => $ward,
            'area' => $area,
            'title' => $title !== '' ? $title : 'Report from residents',
            'summary' => Str::limit($clean, 280, ''),
            'contains_personal_info' => (bool) preg_match('/(\+?254|0)[17]\d{8}/', $text),
            'is_actionable' => mb_strlen($clean) >= 8,
        ], $wardNames, $areaNames);
    }
}

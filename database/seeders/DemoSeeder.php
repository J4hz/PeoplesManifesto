<?php

namespace Database\Seeders;

use App\Actions\AddVoice;
use App\Actions\CreateSubmission;
use App\Actions\PostClusterUpdate;
use App\Actions\RecordRating;
use App\Ai\AnthropicClient;
use App\Ai\SpendGuard;
use App\Ai\TemplateBriefWriter;
use App\Jobs\DispatchWeeklyBriefs;
use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Leader;
use App\Models\User;
use App\Models\Ward;
use App\Services\ClusterRanker;
use App\Services\IdentityHasher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Local demo data so every screen has something on it. Runs only in the local
 * and testing environments. The people, numbers and reports are made up for
 * development and must never be loaded into production.
 *
 * Every demo staff account has the password from DEMO_STAFF_PASSWORD ("password"
 * when unset) and the authenticator secret JBSWY3DPEHPK3PXP, which you can add to
 * an authenticator app by hand.
 */
class DemoSeeder extends Seeder
{
    private const TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    public function run(): void
    {
        config(['queue.default' => 'sync']);

        $kiuu = Ward::where('slug', 'kiuu')->firstOrFail();
        $mwiki = Ward::where('slug', 'mwiki')->firstOrFail();

        // TODO: replace with the real villages and estates from the ward administrator.
        foreach (['Zone A', 'Zone B', 'Zone C', 'Zone D'] as $area) {
            $kiuu->areas()->firstOrCreate(['name' => $area]);
        }

        $this->staffUser('Platform Admin', 'admin@manifesto.test', User::ROLE_ADMIN, null);

        $mca = $this->leader($kiuu->officeOfType('mca')->id, 'Demo MCA for Kiuu', 'demo-mca-kiuu');
        $mcaUser = $this->staffUser('Demo MCA for Kiuu', 'mca.kiuu@manifesto.test', User::ROLE_LEADER, $mca->id);
        $mca->forceFill(['user_id' => $mcaUser->id])->save();
        $this->staffUser('Demo ward office clerk', 'clerk.kiuu@manifesto.test', User::ROLE_STAFF, $mca->id);

        $mp = $this->leader($kiuu->officeOfType('mp')->id, 'Demo MP for Ruiru', 'demo-mp-ruiru');
        $mpUser = $this->staffUser('Demo MP for Ruiru', 'mp.ruiru@manifesto.test', User::ROLE_LEADER, $mp->id);
        $mp->forceFill(['user_id' => $mpUser->id])->save();

        $kiuuResidents = collect(range(1, 24))->map(fn ($i) => $this->citizen($kiuu, $i, $i % 5 === 0 ? 400 : 60));
        $mwikiResidents = collect(range(25, 30))->map(fn ($i) => $this->citizen($mwiki, $i, 120));

        $create = app(CreateSubmission::class);
        $reports = [
            [21, 0, 'No water in our taps for five days now near the stage in Zone A. We are buying from carts at 50 bob a jerrican.', 'water', 'urgent'],
            [18, 1, 'Hakuna maji kwa wiki moja sasa Zone B, tunanunua maji ya mkokoteni. Water haijatoka.', null, null],
            [12, 2, 'Water rationing schedule is not being followed, the taps in Zone C have been dry since last week.', 'water', 'serious'],
            [5, 3, 'Still no water in Zone A, the water tank at the school is also empty.', 'water', 'urgent'],
            [3, 4, 'Maji haijatoka tangu Jumatatu, watoto wanaenda shule bila kuoga. Water please.', 'water', null],
            [20, 5, 'The murram road to the primary school has huge potholes, matatus refuse to come in when it rains.', 'roads', 'serious'],
            [15, 6, 'Barabara imeharibika sana, kuna shimo kubwa karibu na kanisa. Road ni mbaya.', 'roads', null],
            [9, 7, 'Potholes on the road near the market are damaging boda bodas and one rider fell last week.', 'roads', 'serious'],
            [16, 8, 'Street lights near the footbridge are off and people are mugged at night. Security is bad.', 'security', 'urgent'],
            [6, 9, 'Wezi wanavunja nyumba usiku, hakuna polisi wanaopiga doria. Security tafadhali.', 'security', 'serious'],
            [14, 10, 'Garbage has not been collected for three weeks, the dump near the shops smells.', 'sanitation', 'serious'],
            [2, 11, 'Takataka zimejaa kando ya barabara, hakuna gari la county limekuja. Garbage everywhere.', 'sanitation', null],
            [8, 12, 'The dispensary has had no malaria drugs for two weeks. Mothers are sent to buy from chemists.', 'health', 'urgent'],
        ];

        foreach ($reports as [$daysAgo, $resident, $body, $category, $severity]) {
            Carbon::setTestNow(now()->subDays($daysAgo)->setTime(9 + $resident % 8, 15));
            $create->handle($kiuuResidents[$resident], ['body' => $body, 'category' => $category, 'severity' => $severity], null, 'app');
            Carbon::setTestNow();
        }

        foreach ([[4, 25, 'Hakuna maji Mwiki kwa siku tatu, water bowser haijakuja.', 'water'], [2, 26, 'Garbage collection in Mwiki has stopped for two weeks.', 'sanitation']] as [$daysAgo, $resident, $body, $category]) {
            Carbon::setTestNow(now()->subDays($daysAgo));
            $create->handle($mwikiResidents[$resident - 25], ['body' => $body, 'category' => $category], null, 'sms');
            Carbon::setTestNow();
        }

        $water = IssueCluster::where('ward_id', $kiuu->id)->where('category', 'water')->orderByDesc('resident_count')->first();
        $roads = IssueCluster::where('ward_id', $kiuu->id)->where('category', 'roads')->orderByDesc('resident_count')->first();
        $garbage = IssueCluster::where('ward_id', $kiuu->id)->where('category', 'sanitation')->first();

        $voice = app(AddVoice::class);
        foreach ([13, 14, 15, 16, 17] as $resident) {
            $voice->handle($kiuuResidents[$resident], $water);
        }

        $post = app(PostClusterUpdate::class);
        $post->handle($mcaUser, $water->refresh(), [
            'status' => IssueCluster::STATUS_IN_PROGRESS,
            'message' => 'I met the water company on Tuesday. The main pipe at the stage burst and a repair crew starts on Thursday. Water bowsers will serve Zone A and Zone B until then.',
            'expected_completion_on' => now()->addDays(6)->toDateString(),
            'notify_reporters' => true,
        ], null);

        if ($roads) {
            $post->handle($mpUser, $roads, [
                'status' => IssueCluster::STATUS_ACKNOWLEDGED,
                'message' => 'We have the road to the primary school on the NG-CDF list for this quarter. Our office will share the contractor\'s dates once they are signed.',
                'notify_reporters' => true,
            ], null);
        }

        if ($garbage) {
            $post->handle($mcaUser, $garbage, [
                'status' => IssueCluster::STATUS_DONE,
                'message' => 'The county truck collected the waste at the shops on Saturday and will now come every Wednesday.',
                'notify_reporters' => true,
            ], null);

            $rate = app(RecordRating::class);
            $garbage->refresh();
            foreach ($garbage->reporters() as $i => $resident) {
                $rate->handle($resident, $garbage, [
                    'verdict' => $i === 0 ? 'fixed' : 'partly',
                    'stars' => $i === 0 ? 5 : 3,
                    'praise_tags' => $i === 0 ? ['fast_response', 'kept_us_updated'] : ['fast_response'],
                    'thanks' => $i === 0 ? 'Asante, the truck came on Saturday as promised.' : null,
                ], 'app');
            }
        }

        $ranker = app(ClusterRanker::class);
        IssueCluster::all()->each(fn (IssueCluster $cluster) => $ranker->recalculate($cluster));

        (new DispatchWeeklyBriefs)->handle(app(AnthropicClient::class), app(SpendGuard::class), app(TemplateBriefWriter::class));
    }

    private function leader(int $officeId, string $name, string $slug): Leader
    {
        return Leader::updateOrCreate(['slug' => $slug], ['office_id' => $officeId, 'name' => $name, 'is_current' => true]);
    }

    private function staffUser(string $name, string $email, string $role, ?int $leaderId): User
    {
        return User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make((string) config('civic.demo.staff_password')),
            'role' => $role,
            'leader_id' => $leaderId,
            'is_active' => true,
            'two_factor_secret' => encrypt(self::TOTP_SECRET),
            'two_factor_recovery_codes' => encrypt(json_encode(['demo-recovery-1', 'demo-recovery-2'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    private function citizen(Ward $ward, int $n, int $ageDays): Citizen
    {
        // 0700 000 0xx is not issued to subscribers, so demo numbers cannot reach a real person.
        $phone = '+254700000'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        $citizen = Citizen::firstOrNew(['phone_hash' => app(IdentityHasher::class)->phone($phone)]);
        $citizen->forceFill([
            'phone' => $phone,
            'ward_id' => $ward->id,
            'locale' => $n % 3 === 0 ? 'sw' : 'en',
            'verified_at' => now(),
            'created_at' => now()->subDays($ageDays),
        ])->save();

        return $citizen;
    }
}

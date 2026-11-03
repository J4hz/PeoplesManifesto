@php $operator = config('civic.operator'); @endphp
<x-layout.citizen :title="__('privacy.title')">
    <article class="card prose" lang="en">
        <h1>Privacy notice</h1>
        <p>This notice explains what the People's Manifesto keeps about you, why, and what you can ask us to do with it. It is written with the Data Protection Act, 2019 in mind.</p>
        @if ($operator['name'])
            <p>The platform is run by {{ $operator['name'] }}, the data controller.@if ($operator['odpc_registration']) Our registration number with the Office of the Data Protection Commissioner is {{ $operator['odpc_registration'] }}.@endif</p>
        @endif

        <h2>What we keep</h2>
        <ul>
            <li>Your phone number, so you can sign in and get updates by SMS. It is stored encrypted.</li>
            <li>Your ward, so your reports reach the right leaders.</li>
            <li>What you report, any photo you add, and your ratings of finished work.</li>
        </ul>

        <h2>How we keep you anonymous</h2>
        <p>Your reports are stored apart from your phone number. Leaders, their staff and our administrators see reports grouped by issue, with counts of residents. They never see your number or your name. Photos are stripped of location data before they are saved.</p>
        <p>We use an automated system to translate reports into English, sort them by topic and group similar ones together. It does not make decisions about you.</p>

        <h2>Why we use it</h2>
        <p>You give your consent when you sign up. We use your data only to pass your ward's issues to your elected leaders, to tell you when they respond, and to ask whether a fix worked.</p>

        <h2>Your rights</h2>
        <ul>
            <li>You can see your reports at any time under My reports.</li>
            <li>You can switch off SMS updates or delete your account under My account. Deleting removes your phone number and the link between you and your reports. The reports stay on your ward's record without any link to you.</li>
            <li>You can complain to the Office of the Data Protection Commissioner at odpc.go.ke.</li>
        </ul>
        @if ($operator['privacy_email'])
            <p>For any other request, write to <a href="mailto:{{ $operator['privacy_email'] }}">{{ $operator['privacy_email'] }}</a>.</p>
        @endif
        {{-- TODO: confirm the retention period for reports and backups with the operator and add it here. --}}
    </article>

    <article class="card prose" lang="sw">
        <h1>Taarifa ya faragha</h1>
        <p>Taarifa hii inaeleza People's Manifesto inahifadhi nini kukuhusu, kwa nini, na unachoweza kutuomba tufanye. Imeandikwa kwa kuzingatia Sheria ya Ulinzi wa Data, 2019.</p>
        @if ($operator['name'])
            <p>Jukwaa hili linaendeshwa na {{ $operator['name'] }}, ambaye ndiye mdhibiti wa data.@if ($operator['odpc_registration']) Nambari yetu ya usajili na Ofisi ya Kamishna wa Ulinzi wa Data ni {{ $operator['odpc_registration'] }}.@endif</p>
        @endif

        <h2>Tunachohifadhi</h2>
        <ul>
            <li>Nambari yako ya simu, ili uweze kuingia na kupata habari kwa SMS. Inahifadhiwa ikiwa imefichwa kwa usimbaji fiche.</li>
            <li>Wadi yako, ili ripoti zako zifike kwa viongozi wanaohusika.</li>
            <li>Unachoripoti, picha yoyote unayoongeza, na tathmini zako za kazi iliyokamilika.</li>
        </ul>

        <h2>Jinsi tunavyoficha utambulisho wako</h2>
        <p>Ripoti zako zinahifadhiwa mbali na nambari yako ya simu. Viongozi, wafanyakazi wao na wasimamizi wetu wanaona ripoti zikiwa zimepangwa kwa kila tatizo, pamoja na idadi ya wakazi. Hawaoni nambari yako wala jina lako. Taarifa za mahali zinaondolewa kwenye picha kabla hazijahifadhiwa.</p>
        <p>Tunatumia mfumo wa kiotomatiki kutafsiri ripoti kwa Kiingereza, kuzipanga kwa mada na kuweka pamoja zinazofanana. Mfumo huo haufanyi maamuzi kukuhusu.</p>

        <h2>Kwa nini tunaitumia</h2>
        <p>Unatoa idhini yako unapojisajili. Tunatumia data yako tu kufikisha masuala ya wadi yako kwa viongozi uliowachagua, kukujulisha wanapojibu, na kukuuliza kama kazi iliyofanywa ilitatua tatizo.</p>

        <h2>Haki zako</h2>
        <ul>
            <li>Unaweza kuona ripoti zako wakati wowote kwenye Ripoti zangu.</li>
            <li>Unaweza kuzima ujumbe wa SMS au kufuta akaunti yako kwenye Akaunti yangu. Ukifuta, nambari yako ya simu na uhusiano kati yako na ripoti zako vinaondolewa. Ripoti zinabaki kwenye rekodi ya wadi yako bila kukuhusisha.</li>
            <li>Unaweza kuwasilisha malalamiko kwa Ofisi ya Kamishna wa Ulinzi wa Data kupitia odpc.go.ke.</li>
        </ul>
        @if ($operator['privacy_email'])
            <p>Kwa ombi lingine lolote, tuandikie kupitia <a href="mailto:{{ $operator['privacy_email'] }}">{{ $operator['privacy_email'] }}</a>.</p>
        @endif
    </article>
</x-layout.citizen>

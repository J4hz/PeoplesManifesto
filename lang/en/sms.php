<?php

/*
| SMS replies. Keep each one under 160 characters where possible, one SMS page
| costs less and arrives in one piece on basic phones.
*/

return [
    'otp' => 'Your People\'s Manifesto code is :code. It works for :minutes minutes. Do not share it with anyone.',
    'ward_prompt' => 'People\'s Manifesto. Which ward do you live in? Reply with the number: :list. Jibu SW kwa Kiswahili.',
    'invalid_ward' => 'Sorry, we did not get that. Reply with the number of your ward: :list',
    'describe_prompt' => 'What is the problem? Say what, where and since when.',
    'too_short' => 'Please describe the problem in a few more words.',
    'severity_prompt' => 'How serious is it? Reply 1 Annoying, 2 Serious, 3 Urgent.',
    'confirmed' => 'Thank you. Your report number is :id. We will text you when a leader responds. Reply STOP to stop updates.',
    'too_many' => 'You have sent many reports in the last hour. Please try again later.',
    'stopped' => 'You will not get more updates. Text us any time to report a problem.',
    'rating_thanks' => 'Thank you. Your answer is counted.',
    'update' => 'Update on ":title" from :leader: :message',
    'done' => ':leader says ":title" is fixed. Was it? Reply 1 Yes, 2 Partly, 3 No.',
];

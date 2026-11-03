#!/usr/bin/env bash
# Checks the repository against the writing rules in CLAUDE.md.
# CLAUDE.md itself lists the banned words and characters, so it is skipped.
set -u
cd "$(dirname "$0")/.."

files=$(git ls-files --cached --others --exclude-standard \
    | grep -v -E '^(CLAUDE\.md|composer\.lock|package-lock\.json|scripts/check-style\.sh)$' \
    | grep -v -E '\.(png|ico|jpg|woff2?)$')

status=0

report() {
    if [ -n "$2" ]; then
        echo "== $1"
        echo "$2"
        status=1
    fi
}

# Em dash and en dash.
report "Em or en dashes" "$(grep -n -P '\x{2014}|\x{2013}' $files 2>/dev/null)"

# Emoji and pictographs.
report "Emojis" "$(grep -n -P '[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{1F000}-\x{1F2FF}\x{FE0F}]' $files 2>/dev/null)"

# Unescaped Blade output.
report "Unescaped Blade output" "$(grep -n -F '{!!' $(echo "$files" | grep '\.blade\.php$') 2>/dev/null)"

# Buzzwords and stock phrases, as whole words, case-insensitive.
words='unlock|elevate|seamless|seamlessly|supercharge|leverage|empower|revolutionise|revolutionize|game-changer|cutting-edge|state-of-the-art|robust|transformative|harness|unleash|embark|delve|dive in|navigate the landscape|ever-evolving|fast-paced world|take it to the next level|the future of|one-stop|look no further|journey|tapestry|in today.s world|in conclusion'
report "Banned words" "$(grep -n -i -w -E "$words" $files 2>/dev/null)"

# Exclamation marks in user-facing text: views and language files.
report "Exclamation marks in copy" "$(grep -n -E "[A-Za-z]!( |'|\"|<|$)" $(echo "$files" | grep -E '^(resources/views|lang)/') 2>/dev/null | grep -v -E '!=|!\$|<!DOCTYPE|<!--')"

if [ $status -eq 0 ]; then
    echo "Style check passed."
fi
exit $status

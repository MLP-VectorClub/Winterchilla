#!/usr/bin/env python3
"""Turns the output of a Pest run against another implementation into a failure list grouped by file and cause.
   UI_BASE_URL=http://localhost:3000 vendor/bin/pest tests/Browser/{Admin,User,Guest} --exclude-group=winterchilla-only > run.log 2>&1
   scripts/ui-failures.py run.log > failures.md"""
import re, sys, collections
log = open(sys.argv[1]).read()
log = '\n'.join(l for l in log.split('\n') if not re.match(r'^\[\d+\]', l))
passed = len(re.findall(r'^\s+✓', log, re.M))
rows = []
for b in re.split(r'\n\s+FAILED\s+', log)[1:]:
    head, _, rest = b.partition('\n')
    m = re.match(r'Tests\\Browser\\(\w+)\\(\w+) > (?:it )?(.*?)\s*$', head.strip())
    msg = re.sub(r' A screenshot.*', '', rest.strip().split('\n')[0])
    loc = re.search(r'at (tests/\S+):(\d+)', rest)
    cat = 'other'
    if 'ERR_CONNECTION_REFUSED' in msg: cat = 'server down'
    elif 'Expected to see text' in msg: cat = 'text not found'
    elif 'Expected not to see' in msg or 'DontSee' in msg: cat = 'unexpected text'
    elif 'Timeout' in msg: cat = 'selector timeout'
    elif 'to be present in the DOM' in msg or 'element [' in msg: cat = 'selector missing'
    elif 'JavaScript' in msg: cat = 'JS errors'
    elif 'path' in msg.lower(): cat = 'wrong path/redirect'
    rows.append((m.group(1), m.group(2), m.group(3) if m else head, cat, msg[:260], f"{loc.group(1)}:{loc.group(2)}" if loc else ''))
cnt = collections.Counter(r[3] for r in rows)
print(f"# UI tests against another implementation: {passed} passed, {len(rows)} failed\n")
print("By cause: " + ", ".join(f"{k}: {v}" for k, v in cnt.most_common()) + "\n")
cur = None
for r in sorted(rows):
    if (r[0], r[1]) != cur:
        cur = (r[0], r[1]); print(f"\n## {r[0]}/{r[1]}.php")
    print(f"- [{r[3]}] {r[2]} — {r[4]}  ({r[5]})")

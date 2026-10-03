/**
 * Reports the coverage of the page scripts (assets/js): the zero-count coverage of every instrumented file (build/coverage/js-initial.json,
 * written by `COVERAGE=1 pnpm build`) plus the hits the browser tests sent to the test server (build/coverage/js-hits/*.json).
 * Writes build/coverage/js-html/ and build/coverage/js-lcov.info and prints a summary. Run through scripts/coverage.sh.
 */
import fs from 'node:fs';
import path from 'node:path';
import libCoverage from 'istanbul-lib-coverage';
import libReport from 'istanbul-lib-report';
import reports from 'istanbul-reports';

const dir = 'build/coverage';
const initialFile = `${dir}/js-initial.json`;
if (!fs.existsSync(initialFile)) {
  console.error(`No ${initialFile}: build the scripts with COVERAGE=1 pnpm build first`);
  process.exit(1);
}

const map = libCoverage.createCoverageMap({});
for (const cov of Object.values(JSON.parse(fs.readFileSync(initialFile, 'utf-8'))))
  map.addFileCoverage(cov);

const hitsDir = `${dir}/js-hits`;
const hitFiles = fs.existsSync(hitsDir) ? fs.readdirSync(hitsDir).filter(f => f.endsWith('.json')) : [];
for (const name of hitFiles) {
  let page;
  try {
    page = JSON.parse(fs.readFileSync(path.join(hitsDir, name), 'utf-8'));
  } catch {
    continue;
  }
  for (const [file, hits] of Object.entries(page)) {
    if (!map.data[file]) continue;
    const data = map.fileCoverageFor(file).data;
    for (const [k, n] of Object.entries(hits.s)) data.s[k] += n;
    for (const [k, n] of Object.entries(hits.f)) data.f[k] += n;
    for (const [k, counts] of Object.entries(hits.b)) counts.forEach((n, i) => { data.b[k][i] += n; });
  }
}
if (hitFiles.length === 0)
  console.error(`No script coverage found in ${hitsDir} (did the browser tests run against the instrumented build?)`);

const context = libReport.createContext({
  dir: `${dir}/js-html`,
  coverageMap: map,
  defaultSummarizer: 'nested',
});
reports.create('html').execute(context);
reports.create('lcovonly', { file: '../js-lcov.info' }).execute(context);
console.log(`Page scripts (assets/js), ${hitFiles.length} page loads reported`);
reports.create('text-summary').execute(context);
console.log(`HTML report: ${dir}/js-html/index.html`);

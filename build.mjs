import * as esbuild from 'esbuild';
import * as sass from 'sass';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';
import { glob } from 'glob';
import chokidar from 'chokidar';
import fs from 'node:fs';
import path from 'node:path';
import dotenv from 'dotenv';

dotenv.config();

const isWatch = process.argv.includes('--watch');
const lockfilePath = process.env.NPM_BUILD_LOCK_FILE_PATH;

function lock() {
  if (lockfilePath) fs.closeSync(fs.openSync(lockfilePath, 'a'));
}

function unlock() {
  if (lockfilePath) {
    try { fs.unlinkSync(lockfilePath); } catch { /* already gone */ }
  }
}

function clean() {
  fs.rmSync('public/js', { recursive: true, force: true });
  fs.rmSync('public/css', { recursive: true, force: true });
}

function toOutputPath(srcFile, srcDir, outDir, suffix) {
  const rel = path.relative(srcDir, srcFile);
  const { dir, name } = path.parse(rel);
  return path.join(outDir, dir, name + suffix);
}

async function buildJS(file) {
  const source = fs.readFileSync(file, 'utf-8');
  const result = await esbuild.transform(source, {
    loader: 'jsx',
    target: 'es2019',
    minify: true,
    sourcemap: true,
    sourcefile: file,
  });
  const out = toOutputPath(file, 'assets/js', 'public/js', '.min.js');
  const mapFilename = path.basename(out) + '.map';
  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.writeFileSync(out, result.code + `\n//# sourceMappingURL=${mapFilename}`);
  fs.writeFileSync(out + '.map', result.map);
}

async function buildSCSS(file) {
  if (path.basename(file).startsWith('_')) return;
  const compiled = sass.compile(file, { silenceDeprecations: ['import', 'global-builtin'] });
  const processed = await postcss([autoprefixer]).process(compiled.css, { from: undefined });
  const minified = await esbuild.transform(processed.css, { loader: 'css', minify: true });
  const out = toOutputPath(file, 'assets/scss', 'public/css', '.min.css');
  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.writeFileSync(out, minified.code);
}

async function buildAllJS() {
  const files = await glob('assets/js/**/*.{js,jsx}');
  await Promise.all(files.map(buildJS));
  console.log(`[js] Built ${files.length} files`);
}

async function buildAllSCSS() {
  const files = await glob('assets/scss/**/*.scss');
  const entryFiles = files.filter(f => !path.basename(f).startsWith('_'));
  await Promise.all(entryFiles.map(buildSCSS));
  console.log(`[scss] Built ${entryFiles.length} files`);
}

async function main() {
  if (!isWatch) lock();
  clean();
  await Promise.all([buildAllJS(), buildAllSCSS()]);

  if (!isWatch) {
    unlock();
    return;
  }

  console.log('[watch] Watching for changes...');

  chokidar.watch('assets/js', { ignoreInitial: true })
    .on('change', file => {
      if (!/\.jsx?$/.test(file)) return;

      console.log(`[js] ${file}`);
      buildJS(file).catch(e => console.error('[js] Error:', e.message));
    })
    .on('add', file => {
      if (!/\.jsx?$/.test(file)) return;

      console.log(`[js] ${file}`);
      buildJS(file).catch(e => console.error('[js] Error:', e.message));
    });

  // Rebuild all SCSS on any change since partials affect multiple output files
  chokidar.watch('assets/scss', { ignoreInitial: true })
    .on('change', file => {
      if (!/\.scss$/.test(file)) return;

      console.log(`[scss] ${file}`);
      buildAllSCSS().catch(e => console.error('[scss] Error:', e.message));
    })
    .on('add', file => {
      if (!/\.scss$/.test(file)) return;

      console.log(`[scss] ${file}`);
      buildAllSCSS().catch(e => console.error('[scss] Error:', e.message));
    });
}

main().catch(e => {
  console.error(e);
  unlock();
  process.exit(1);
});

const esbuild = require('esbuild');

const isWatch = process.argv.includes('--watch');

const config = {
  entryPoints: ['src/main.js'],
  bundle: true,
  minify: !isWatch,
  outfile: 'assets/uc-calc.js',
  format: 'iife',
  platform: 'browser',
  target: ['es2017', 'safari15'],
  logLevel: 'info',
};

if (isWatch) {
  esbuild.context(config).then(ctx => ctx.watch());
} else {
  esbuild.build(config).catch(() => process.exit(1));
}

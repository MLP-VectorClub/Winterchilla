// Used by CoreUtils::minifySvgData() to minify user-uploaded SVGs (e.g. cutie marks).
// Keep path/number/shape data intact so the output stays faithful to the uploaded artwork
// and so later color tokenization (CGUtils::tokenizeSvg) sees the fills/strokes it expects.
export default {
  plugins: [
    {
      name: 'preset-default',
      params: {
        overrides: {
          removeUnknownsAndDefaults: false,
          removeUselessStrokeAndFill: false,
          convertPathData: false,
          convertTransform: false,
          cleanupNumericValues: false,
          mergePaths: false,
          convertShapeToPath: false,
        },
      },
    },
    'removeRasterImages',
    'removeDimensions',
  ],
};

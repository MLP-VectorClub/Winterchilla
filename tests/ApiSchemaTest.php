<?php

use App\CoreUtils;

// The generated OpenAPI document is the interface Celestia's type generator (packages/api-types) and Luna's implementation are
// checked against, so a dangling $ref, a duplicated operation ID or a swagger-php warning must not reach main.

function generatedApiSchema():array {
  static $schema = null;
  if ($schema === null) {
    $warnings = [];
    set_error_handler(function (int $no, string $message) use (&$warnings) {
      // The docblock-annotations deprecation notice is a known, separate migration
      if ($no !== E_USER_DEPRECATED && $no !== E_DEPRECATED)
        $warnings[] = $message;

      return true;
    });
    try {
      CoreUtils::generateApiSchema();
    }
    finally {
      restore_error_handler();
    }
    if (!empty($warnings))
      throw new RuntimeException("swagger-php reported problems:\n".implode("\n", $warnings));
    $schema = json_decode(file_get_contents(APPATH.API_SCHEMA_PATH), true, 512, JSON_THROW_ON_ERROR);
  }

  return $schema;
}

function collectRefs($node, array &$refs):void {
  if (!is_array($node))
    return;
  foreach ($node as $key => $value) {
    if ($key === '$ref' && is_string($value))
      $refs[] = $value;
    else collectRefs($value, $refs);
  }
}

it('generates without swagger-php warnings', function () {
  expect(generatedApiSchema())->toHaveKeys(['openapi', 'paths', 'components']);
});

it('gives every operation a unique, readable operation ID', function () {
  $ids = [];
  foreach (generatedApiSchema()['paths'] as $path => $operations) {
    foreach ($operations as $method => $operation) {
      if (!is_array($operation) || !isset($operation['operationId']))
        continue;
      expect($operation['operationId'])->toMatch('/^[A-Z][A-Za-z0-9]+$/', "$method $path");
      $ids[] = $operation['operationId'];
    }
  }

  expect($ids)->not->toBeEmpty()->and(array_unique($ids))->toHaveCount(count($ids));
});

it('resolves every $ref to a component', function () {
  $schema = generatedApiSchema();
  $refs = [];
  collectRefs($schema, $refs);

  foreach (array_unique($refs) as $ref) {
    expect($ref)->toStartWith("#/components/");
    [, , $group, $name] = explode('/', $ref) + [null, null, null, null];
    expect($schema['components'][$group] ?? [])->toHaveKey($name);
  }
});

it('uses Luna-style resource paths', function () {
  // Winterchilla's old prefixes are gone; /cg/full is the one UI-only path that kept its name
  $paths = array_diff(array_keys(generatedApiSchema()['paths']), ['/cg/full']);

  foreach ($paths as $path)
    expect($path)->not->toMatch('#^/(cg|post|event|notif|setting|user|admin/usefullinks)(/|$)#');
});

it('marks the UI-only and Winterchilla-specific operations as x-internal', function () {
  $schema = generatedApiSchema();
  $internal = [];
  foreach ($schema['paths'] as $path => $operations) {
    foreach ($operations as $method => $operation) {
      if (is_array($operation) && ($operation['x-internal'] ?? false) === true)
        $internal[] = strtoupper($method) . " $path";
    }
  }

  // Every entry of the list matches a documented operation (a rename must not silently drop the marker)...
  expect($internal)->toEqualCanonicalizing(CoreUtils::INTERNAL_OPERATIONS);
  // ...and an operation whose only purpose is a rendered-HTML body is on it
  foreach (['GET /about/upcoming', 'GET /cg/full', 'GET /posts/{id}/lazyload', 'GET /show/{id}/posts'] as $pure_ui)
    expect($internal)->toContain($pure_ui);
});

it('keeps every operation attached to its method (a docblock inserted between an @OA block and its method drops it silently)', function () {
  $paths = generatedApiSchema()['paths'];

  expect($paths['/show/{id}/vote'])->toHaveKeys(['get', 'post']);

  $operations = 0;
  foreach ($paths as $methods)
    $operations += count(array_intersect_key($methods, array_flip(['get', 'post', 'put', 'patch', 'delete'])));
  expect($operations)->toBeGreaterThanOrEqual(149);
});

it('only lists properties that exist in a schema\'s required list', function () {
  foreach (generatedApiSchema()['components']['schemas'] as $name => $schema) {
    if (isset($schema['allOf']) || empty($schema['required']))
      continue;
    foreach ($schema['required'] as $property)
      expect(array_key_exists($property, $schema['properties'] ?? []))->toBeTrue("$name requires missing property $property");
  }
});

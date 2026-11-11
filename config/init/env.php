<?php

// Load environment variables \\
// In containers the variables come from the real environment (Kubernetes env/Secret) and there is no .env file.
$env_path = PROJPATH.'.env';
if (file_exists($env_path)) {
  $env = new Symfony\Component\Dotenv\Dotenv();
  $env->load($env_path);
}
elseif (empty($_ENV['LOG_PATH']) && empty(getenv('LOG_PATH')))
  die("Environment file not found at $env_path and LOG_PATH is not set in the environment");
else {
  // php-fpm/CLI may not populate $_ENV (variables_order), so mirror the real environment into it
  foreach (getenv() as $k => $v) {
    $_ENV[$k] ??= $v;
  }
}

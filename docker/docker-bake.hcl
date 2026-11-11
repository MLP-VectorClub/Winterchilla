# Builds the two images (fpm, web) from the repository root:
#   docker buildx bake -f docker/docker-bake.hcl --set '*.platform=linux/arm64'
#   REGISTRY=ghcr.io/mlp-vectorclub TAGS=abc1234 docker buildx bake -f docker/docker-bake.hcl --push
variable "REGISTRY" { default = "localhost:5000" }
variable "TAGS" { default = "dev" }
variable "GIT_COMMIT_HASH" { default = "unknown" }

group "default" { targets = ["fpm", "web"] }

target "_common" {
  context    = "."
  dockerfile = "docker/Dockerfile"
  platforms  = ["linux/amd64", "linux/arm64"]
  args = {
    GIT_COMMIT_HASH = GIT_COMMIT_HASH
  }
}

target "fpm" {
  inherits = ["_common"]
  target   = "fpm"
  tags     = [for t in split(",", TAGS) : "${REGISTRY}/winterchilla-fpm:${t}"]
}
target "web" {
  inherits = ["_common"]
  target   = "web"
  tags     = [for t in split(",", TAGS) : "${REGISTRY}/winterchilla-web:${t}"]
}

#!/usr/bin/env bash
set -euo pipefail

VERSION=$(node -e "process.stdout.write(require('./composer.json').version)")

sonar-scanner \
  -Dsonar.projectVersion="${VERSION}" \
  -Dproject.settings=sonar-project.properties

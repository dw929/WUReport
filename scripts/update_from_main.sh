#!/usr/bin/env bash
set -euo pipefail

MAIN_BRANCH="${MAIN_BRANCH:-main}"
REMOTE_NAME="${REMOTE_NAME:-origin}"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: this script must be run from inside a Git repository." >&2
  exit 1
fi

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Error: working tree is not clean. Commit or stash changes before updating." >&2
  exit 1
fi

if ! git remote get-url "$REMOTE_NAME" >/dev/null 2>&1; then
  echo "Error: remote '$REMOTE_NAME' does not exist." >&2
  exit 1
fi

echo "Fetching latest changes from $REMOTE_NAME..."
git fetch "$REMOTE_NAME"

if ! git show-ref --verify --quiet "refs/remotes/$REMOTE_NAME/$MAIN_BRANCH"; then
  echo "Error: branch '$MAIN_BRANCH' not found on remote '$REMOTE_NAME'." >&2
  exit 1
fi

echo "Updating branch '$CURRENT_BRANCH' with '$REMOTE_NAME/$MAIN_BRANCH'..."
git merge --ff-only "$REMOTE_NAME/$MAIN_BRANCH"

echo "Update complete. '$CURRENT_BRANCH' is now up to date with '$REMOTE_NAME/$MAIN_BRANCH'."

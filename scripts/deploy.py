#!/usr/bin/env python3
"""
Local FTP deploy for davenhamscouts.org.uk

Replaces the GitHub Actions FTP deploy. Tracks a local state file
(.deploy-state.json) so subsequent runs only upload changed files.

Usage:
    ./deploy.sh                       # upload everything that changed since last deploy
    ./deploy.sh path1 path2 ...       # upload only the given paths
    ./deploy.sh --dry-run             # show what would be uploaded, do nothing
    ./deploy.sh --all                 # force re-upload of everything (ignore state)

Requires the FTP password in env var DAVENHAM_FTP_PASSWORD, or in a file
at ./.ftp-credentials (single line: just the password, no quotes).
"""

import argparse
import fnmatch
import ftplib
import hashlib
import json
import os
import sys
from pathlib import Path

# Directories to skip entirely (matched by name at any depth)
EXCLUDE_DIR_NAMES = {
    ".git", ".github", ".claude",
    "node_modules", "website-core", "tmp", "scripts",
}

# Top-level files to skip (relative to repo root)
EXCLUDE_TOP_FILES = {
    "CLAUDE.md", "design.md", "homepage-builder-example.html",
    "deploy.sh", "DEPLOY.md", ".gitignore",
    ".ftp-credentials", ".deploy-state.json", ".ftp-deploy-sync-state.json",
    ".git",  # worktree's .git is a file pointing to the real .git dir
}

# Filename patterns to skip anywhere (basename match)
EXCLUDE_FILE_PATTERNS = [
    ".DS_Store", "Thumbs.db", "*.swp", "*.swo",
]

FTP_HOST = "s96.lon.krystal.io"
FTP_USER = "agent@davenhamscouts.org.uk"
FTP_PORT = 21
REPO_ROOT = Path(__file__).resolve().parent.parent
STATE_FILE = REPO_ROOT / ".deploy-state.json"
CRED_FILE = REPO_ROOT / ".ftp-credentials"


def log(msg):
    print(msg, flush=True)


def load_password():
    pw = os.environ.get("DAVENHAM_FTP_PASSWORD")
    if pw:
        return pw.strip()
    if CRED_FILE.exists():
        return CRED_FILE.read_text().strip()
    log("ERROR: FTP password not found.")
    log("Set it one of two ways:")
    log("  1. export DAVENHAM_FTP_PASSWORD='your-password'")
    log(f"  2. echo 'your-password' > {CRED_FILE}")
    sys.exit(2)


def is_excluded(rel_path):
    parts = rel_path.split("/")
    # Any path segment matches an excluded directory name
    if any(p in EXCLUDE_DIR_NAMES for p in parts[:-1]):
        return True
    # Top-level file exclusions (depth 1)
    if len(parts) == 1 and parts[0] in EXCLUDE_TOP_FILES:
        return True
    # Basename pattern exclusions
    basename = parts[-1]
    for pattern in EXCLUDE_FILE_PATTERNS:
        if fnmatch.fnmatch(basename, pattern):
            return True
    return False


def file_signature(path):
    """Return (size, mtime_int, sha1_8) — cheap-ish signature for change detection."""
    st = path.stat()
    h = hashlib.sha1()
    with path.open("rb") as f:
        for chunk in iter(lambda: f.read(65536), b""):
            h.update(chunk)
    return [st.st_size, int(st.st_mtime), h.hexdigest()[:16]]


def walk_files():
    """Yield (rel_path_str, abs_path) for every file under REPO_ROOT not excluded."""
    for abs_path in REPO_ROOT.rglob("*"):
        if not abs_path.is_file():
            continue
        rel = abs_path.relative_to(REPO_ROOT).as_posix()
        if is_excluded(rel):
            continue
        yield rel, abs_path


def load_state():
    if not STATE_FILE.exists():
        return {}
    try:
        return json.loads(STATE_FILE.read_text())
    except json.JSONDecodeError:
        log(f"WARN: {STATE_FILE} corrupted, treating as empty")
        return {}


def save_state(state):
    STATE_FILE.write_text(json.dumps(state, indent=2, sort_keys=True))


def ensure_remote_dirs(ftp, remote_rel_path):
    """cwd to root, then mkdir/cwd each path segment as needed."""
    ftp.cwd("/")
    parts = remote_rel_path.split("/")[:-1]
    for part in parts:
        if not part:
            continue
        try:
            ftp.cwd(part)
        except ftplib.error_perm:
            try:
                ftp.mkd(part)
                ftp.cwd(part)
            except ftplib.error_perm as e:
                log(f"  ERROR creating dir {part}: {e}")
                raise


def upload_file(ftp, rel_path, abs_path):
    ensure_remote_dirs(ftp, rel_path)
    filename = rel_path.split("/")[-1]
    with abs_path.open("rb") as f:
        ftp.storbinary(f"STOR {filename}", f)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("paths", nargs="*", help="Specific paths to upload (relative to repo root). If omitted, upload everything changed.")
    parser.add_argument("--dry-run", action="store_true", help="Show what would change without uploading")
    parser.add_argument("--all", action="store_true", help="Re-upload everything, ignore state file")
    args = parser.parse_args()

    os.chdir(REPO_ROOT)

    state = {} if args.all else load_state()
    explicit_paths = {p.lstrip("./") for p in args.paths} if args.paths else None

    # Build the candidate set
    to_check = []
    if explicit_paths:
        for p in explicit_paths:
            abs_p = REPO_ROOT / p
            if not abs_p.exists() or not abs_p.is_file():
                log(f"WARN: skipping non-existent file: {p}")
                continue
            if is_excluded(p):
                log(f"WARN: skipping excluded path: {p}")
                continue
            to_check.append((p, abs_p))
    else:
        to_check = list(walk_files())

    # Diff against state
    changed = []
    for rel, abs_p in to_check:
        sig = file_signature(abs_p)
        if explicit_paths or state.get(rel) != sig:
            changed.append((rel, abs_p, sig))

    if not changed:
        log("Nothing to upload. Site is up to date.")
        return 0

    log(f"Files to upload: {len(changed)}")
    for rel, _, _ in changed[:20]:
        log(f"  + {rel}")
    if len(changed) > 20:
        log(f"  ... and {len(changed) - 20} more")

    if args.dry_run:
        log("(dry run — nothing uploaded)")
        return 0

    password = load_password()
    log(f"Connecting to {FTP_HOST} as {FTP_USER} ...")
    with ftplib.FTP() as ftp:
        ftp.connect(FTP_HOST, FTP_PORT, timeout=30)
        ftp.login(FTP_USER, password)
        log("Connected.")

        for i, (rel, abs_p, sig) in enumerate(changed, 1):
            log(f"[{i}/{len(changed)}] {rel}")
            try:
                upload_file(ftp, rel, abs_p)
                state[rel] = sig
            except Exception as e:
                log(f"  FAILED: {e}")
                save_state(state)
                log("State saved up to last successful file. Re-run to continue.")
                return 1

    save_state(state)
    log(f"Done. Uploaded {len(changed)} file(s).")
    return 0


if __name__ == "__main__":
    sys.exit(main())

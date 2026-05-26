# Local Deploy

The site is no longer deployed via GitHub Actions (Actions budget hit). Use `./deploy.sh` from your laptop instead.

## First-time setup (once per machine)

Save your FTP password locally — never commit it:

```bash
echo 'your-ftp-password-here' > .ftp-credentials
```

(`.ftp-credentials` is gitignored, so it won't be pushed.)

Alternatively, export it in your shell:

```bash
export DAVENHAM_FTP_PASSWORD='your-ftp-password-here'
```

## Daily use

```bash
# See what would change (no upload)
./deploy.sh --dry-run

# Upload everything that changed since last deploy
./deploy.sh

# Upload specific files only
./deploy.sh wp-content/themes/the-scouts-skills-for-life/style.css \
            wp-content/themes/the-scouts-skills-for-life/front-page.php

# Force re-upload of everything
./deploy.sh --all
```

The script keeps a local `.deploy-state.json` recording what's already uploaded, so subsequent runs are fast — only changed files transfer.

## How it works

- Connects to `s96.lon.krystal.io:21` as `agent@davenhamscouts.org.uk`
- Walks the repo, applies the same exclude rules the old GitHub Actions workflow used (skips `.git`, `node_modules`, `website-core/`, `CLAUDE.md`, etc.)
- Hashes each candidate file and compares against `.deploy-state.json` to find changes
- Uploads changed files via FTP, creating remote directories as needed
- Updates `.deploy-state.json` after each successful upload, so an interrupted run can be resumed

## If something goes wrong

- **"FTP password not found"** — set `DAVENHAM_FTP_PASSWORD` or create `.ftp-credentials` (see setup above)
- **Connection timeout** — Krystal occasionally throttles; just re-run
- **Wrong remote dir** — script uploads relative to the FTP user's home, which Krystal sets to the web root
- **Need to redeploy untouched files** — `rm .deploy-state.json && ./deploy.sh` or `./deploy.sh --all`

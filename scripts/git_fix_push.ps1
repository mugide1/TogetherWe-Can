<#
PowerShell helper to diagnose and safely fix a non-fast-forward git push.
Usage:
  # Run diagnostics only
  .\scripts\git_fix_push.ps1

  # Run diagnostics and interactively rebase, push backup to origin, then force-push with lease
  .\scripts\git_fix_push.ps1 -AutoRebase -PushBackup

Notes:
- Script creates a local backup branch `backup/YYYYMMDD-HHMMSS-pre-publish` before mutating history.
- If you allow push of the backup, it will be pushed to `origin`.
- Rebase will rewrite local commits; after a successful rebase the script will push with --force-with-lease when confirmed.
#>
param(
    [switch]$AutoRebase,
    [switch]$PushBackup,
    [string]$RemoteName = "",
    [string]$RemoteBranch = "main"
)
function Run-Git([string]$args) {
    Write-Host "\n> git $args" -ForegroundColor Cyan
    & git $args
}

Write-Host "Repository diagnostics for: $(Get-Location)" -ForegroundColor Green
Run-Git 'branch --show-current'
Run-Git 'remote -v'

# Determine remote to use
$remotes = & git remote 2>$null | ForEach-Object { $_ }
if ([string]::IsNullOrWhiteSpace($RemoteName)) {
    if ($remotes -contains 'origin') {
        $RemoteName = 'origin'
    } elseif ($remotes.Count -eq 1) {
        $RemoteName = $remotes[0]
    } elseif ($remotes.Count -gt 1) {
        Write-Host "Multiple remotes found: $($remotes -join ', ')" -ForegroundColor Yellow
        $pick = Read-Host "Enter remote to use (or press Enter to abort)"
        if ([string]::IsNullOrWhiteSpace($pick)) {
            Write-Host "No remote selected. Aborting." -ForegroundColor Red
            exit 1
        }
        $RemoteName = $pick
    } else {
        Write-Host "No git remotes configured. Please add a remote (eg. 'git remote add origin <url>')." -ForegroundColor Red
        exit 1
    }
}

Write-Host "Using remote: $RemoteName" -ForegroundColor Cyan

Run-Git 'fetch --all --prune'
Run-Git 'status --porcelain --branch'
Run-Git 'rev-list --left-right --count origin/main...HEAD'
Run-Git 'log --oneline --graph --decorate --all -n 50'
Run-Git 'config --get user.name'
Run-Git 'config --get user.email'
Run-Git 'reflog -n 20'
Run-Git 'diff --name-status'
Run-Git 'status'

# Create backup branch
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupBranch = "backup/$timestamp-pre-publish"
Write-Host "\nCreating local backup branch: $backupBranch" -ForegroundColor Yellow
$create = git branch $backupBranch
if ($LASTEXITCODE -ne 0) {
    Write-Host "Failed to create backup branch. Aborting." -ForegroundColor Red
    exit 1
}
Write-Host "Backup branch created locally: $backupBranch" -ForegroundColor Green

if ($PushBackup) {
    Write-Host "Pushing backup branch to $RemoteName..." -ForegroundColor Yellow
    git push $RemoteName "$backupBranch"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to push backup branch to $RemoteName. You can push it manually later: git push $RemoteName $backupBranch" -ForegroundColor Red
    } else {
        Write-Host "Backup branch pushed to $RemoteName/$backupBranch" -ForegroundColor Green
    }
} else {
    $ans = Read-Host "Push backup branch to origin now? (y/N)"
    if ($ans -match '^[Yy]') {
        git push $RemoteName "$backupBranch"
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Failed to push backup branch to $RemoteName. You can push it manually later: git push $RemoteName $backupBranch" -ForegroundColor Red
        } else {
            Write-Host "Backup branch pushed to $RemoteName/$backupBranch" -ForegroundColor Green
        }
    } else {
        Write-Host "Backup branch not pushed. You can push it later with: git push $RemoteName $backupBranch" -ForegroundColor Yellow
    }
}

# Ask to proceed with rebase
if (-not $AutoRebase) {
    $proceed = Read-Host "Proceed to fetch + rebase local branch onto origin/main? This may rewrite local history. (y/N)"
    if (-not ($proceed -match '^[Yy]')) {
        Write-Host "Aborting before rebase. You can run the script with -AutoRebase to skip prompts." -ForegroundColor Yellow
        exit 0
    }
} else {
    Write-Host "AutoRebase enabled: proceeding to fetch + rebase." -ForegroundColor Cyan
}

# Perform fetch and rebase
git fetch origin --prune
git rebase origin/main
Write-Host "Fetching $RemoteName..." -ForegroundColor Cyan
git fetch $RemoteName --prune
if ($LASTEXITCODE -ne 0) { Write-Host "git fetch failed for $RemoteName" -ForegroundColor Red; exit 1 }

Write-Host "Rebasing onto $RemoteName/$RemoteBranch..." -ForegroundColor Cyan
git rebase $RemoteName/$RemoteBranch
if ($LASTEXITCODE -ne 0) {
    Write-Host "Rebase encountered conflicts or failed. Resolve conflicts, then run 'git rebase --continue'. To abort: 'git rebase --abort'." -ForegroundColor Red
    exit 2
}

Write-Host "Rebase completed successfully." -ForegroundColor Green

# Push changes
if (-not $AutoRebase) {
    $pushNow = Read-Host "Push rebased branch to origin (will use --force-with-lease)? (y/N)"
    if (-not ($pushNow -match '^[Yy]')) {
        Write-Host "Skipping push. After review, push with: git push origin --force-with-lease" -ForegroundColor Yellow
        exit 0
    }
}

git push origin --force-with-lease
Write-Host "Pushing with --force-with-lease to $RemoteName..." -ForegroundColor Cyan
git push $RemoteName --force-with-lease
if ($LASTEXITCODE -ne 0) {
    Write-Host "Push failed. If remote has protections, consider merging via Pull Request or coordinate with repository admins." -ForegroundColor Red
    exit 3
}

Write-Host "Push succeeded. You're up-to-date with remote." -ForegroundColor Green
Write-Host "If everything looks good, you can delete the backup branch locally and remotely when ready:" -ForegroundColor Cyan
Write-Host "  git branch -d $backupBranch" -ForegroundColor White
Write-Host "  git push origin --delete $backupBranch" -ForegroundColor White

exit 0

# Run from the extracted repository root. Requires Git, GitHub CLI and `gh auth login`.
param([string]$RepositoryName = "zarinpal-gravityforms-v2.4")
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if (-not (Get-Command git -ErrorAction SilentlyContinue)) { throw "Git is not installed." }
if (-not (Get-Command gh -ErrorAction SilentlyContinue)) { throw "GitHub CLI is not installed. See https://cli.github.com/" }

& gh auth status
if ($LASTEXITCODE -ne 0) { throw "Run: gh auth login" }
$Login = (& gh api user --jq .login).Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($Login)) { throw "Could not determine authenticated GitHub user." }
$FullName = "$Login/$RepositoryName"

& gh repo view $FullName --json name *> $null
if ($LASTEXITCODE -eq 0) { throw "Repository $FullName already exists; no files were changed." }

if (-not (Test-Path "./dist/ZarinPal-GravityForms-2.4.0.zip")) { throw "Release ZIP is missing from dist/." }
if (-not (Test-Path "./.git")) {
    & git init
    & git branch -M main
}
$GitName = (& git config --get user.name)
if ([string]::IsNullOrWhiteSpace(($GitName -join ""))) { & git config user.name $Login }
$GitEmail = (& git config --get user.email)
if ([string]::IsNullOrWhiteSpace(($GitEmail -join ""))) {
    $UserId = (& gh api user --jq .id).Trim()
    if ($LASTEXITCODE -ne 0) { throw "Could not determine the GitHub user id for no-reply email." }
    & git config user.email "$UserId+$Login@users.noreply.github.com"
}
& git add .
& git commit -m "Release independent ZarinPal Gravity Forms v2.4.0 preview with original-project attribution"
if ($LASTEXITCODE -ne 0) { throw "Git commit failed; set git user.name and user.email first." }

& gh repo create $FullName --public --source . --remote origin --push --description "Unofficial ZarinPal REST API v4 payment add-on for Gravity Forms; independent 2.4 rewrite of legacy integration"
if ($LASTEXITCODE -ne 0) { throw "Repository creation or push failed. No release was created." }

& gh release create "v2.4.0" "./dist/ZarinPal-GravityForms-2.4.0.zip" --repo $FullName --title "v2.4.0 - preview (staging validation required)" --notes-file "./RELEASE_NOTES_v2.4.0.md" --prerelease
if ($LASTEXITCODE -ne 0) { throw "Repository created, but GitHub Release creation failed." }
Write-Host "Published repository: https://github.com/$FullName"
Write-Host "Preview release: https://github.com/$FullName/releases/tag/v2.4.0"

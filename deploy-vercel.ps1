# Run this after: npx vercel login
# 1. Login once (opens browser):
#    npx vercel login
# 2. Deploy frontend (set VITE_API_BASE to your backend URL first, or use placeholder):
$apiUrl = $env:VITE_API_BASE
if (-not $apiUrl) { $apiUrl = "https://placeholder.com" }
$env:VITE_API_BASE = $apiUrl
Set-Location $PSScriptRoot\frontend
npx vercel --prod --yes

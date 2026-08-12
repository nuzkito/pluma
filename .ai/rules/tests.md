---
paths:
  - 'tests/**'
---

# Tests

## Test suite layout and helpers
`tests/Pest.php` scaffolds the site automatically: a `beforeEach` calls `initializeSite()` for everything under `Feature/Domain/Editor`, `Feature/Domain/Generator`, `Feature/Domain/Settings`, `Feature/Http` and `Feature/Livewire`. Do not call it inside a test body there; `Feature/Console` files that need it declare their own `beforeEach`.

Use the helpers instead of hand-building fixtures: `aPage()`, `aPublishedPage()`, `aTagPage()` build and save a page; `repository()` and `disk()` reach the scaffolded repository and disk; `toBeOk()`, `toBeError()`, `toExistOnDisk()` and `toBeMissingFromDisk()` are the expectations.

Resolve actions with `app(Action::class)` and call them directly, never `->__invoke()`. To assert on `SiteGenerator` calls, bind the double with `Pest\Laravel\mock()` — the global `mock()` is Mockery's and never reaches the container.

`tests/Unit` is for code that needs no site scaffold (value objects, repositories on a bare `Storage::fake`). Anything that scaffolds a site or boots the app belongs in `tests/Feature`.

Do not add `Carbon::setTestNow(null)` cleanups: Laravel already resets the test clock in `tearDown`.

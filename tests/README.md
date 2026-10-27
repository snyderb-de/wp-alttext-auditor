# Security regression tests

Run from the repository root with PHP 7.4 or later, DOM, and mbstring. On a Nix-enabled machine:

```sh
fixture_dir=$(mktemp -d)
nix shell nixpkgs#php83 --command php tests/security-regression.php "$PWD" "$fixture_dir"
```

The suite invokes the real templates, AJAX handlers, report persistence, report endpoint, and scan cleanup methods. WordPress capabilities, nonces, database services, and hooks use explicit test doubles; the suite does not connect to a real site. Report responses run in isolated subprocesses. Temporary uploads fixtures stay in the supplied disposable directory.

Coverage includes both sorting sinks, alternate injection forms, object permissions before any write, authorized edits, valid post-only/stale-attachment edits, cancellation permissions, report access and nonce checks, cross-site isolation, legacy migration and retry, retention, deletion failures, and JSON link escaping.

Live WordPress/browser acceptance and hosting/CDN cache removal remain deployment checks. The existing post-content editor emits an mbstring deprecation notice on PHP 8.3; these security fixes preserve that editor's behavior.

# Historical baseline — 2.4.2 — DO NOT DEPLOY

The five PHP source files and LICENSE are byte-for-byte identical to the locally audited installer. Verified Git blob SHA-1 values:

| Path | Blob SHA-1 |
|---|---|
| index.php | 08939e3a574b91d5e0478e0b4222991013ccc6d1 |
| includes/class-gf-zarinpal-v24.php | 2119588807a379350f498836eaa22878f3e52555 |
| includes/class-zp24-api.php | c8a006028ad1ec3a19c890f49c1498497b491b49 |
| includes/class-zp24-migrator.php | 061ce0e0c71c35698611309901b22c993c70ee93 |
| includes/class-zp24-persian-compat.php | 3281954bd24b9f46d8fcf472f4c6912e6b4f547d |
| LICENSE | 9efa6fbc962836e243e20f7f23db062e2c077d28 |

Original installer SHA-256: 4b160276e9681854ae07f2a9220792132edb3ee96b88887346cf6e58ffe0053a

This branch is a historical source baseline, not a new release or a safe fallback. Project documentation is updated to reflect the release hold. The previous installer and publishing scripts are omitted here. The complete original archive and private audit fixtures are preserved separately by the maintainer. Do not install a source archive from this branch.

Three reviews established 46 modeled observations, with overlap. They are not 46 confirmed independent vulnerabilities. The 85 original smoke tests were isolated, not production certification. See docs/V2_5_RELEASE_GATES.md for the acceptance criteria.

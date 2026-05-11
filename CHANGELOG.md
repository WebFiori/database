# Changelog

## [2.1.1](https://github.com/WebFiori/database/compare/v2.1.0...v2.1.1) (2026-05-11)


### Features

* **connection:** add connection pooling for database connection reuse ([32d8fb3](https://github.com/WebFiori/database/commit/32d8fb3a9cc67980be3f55ca85bbc003b4a18399)), closes [#143](https://github.com/WebFiori/database/issues/143)


### Miscellaneous Chores

* Updated Version ([c2c3108](https://github.com/WebFiori/database/commit/c2c31080bc7cd8b94218699c6399b14bc08f8dab))

## [2.1.0](https://github.com/WebFiori/database/compare/v2.0.3...v2.1.0) (2026-05-10)


### Features

* **migrations:** surface errors in dry run pending changes ([2cc91b9](https://github.com/WebFiori/database/commit/2cc91b94d6d78e2259c8fd028f692237a70dcd17)), closes [#129](https://github.com/WebFiori/database/issues/129)
* **schema:** add skip/baseline support to SchemaRunner ([98ff685](https://github.com/WebFiori/database/commit/98ff68529f8d8e8e38aee87e808b54dd3673a13b)), closes [#136](https://github.com/WebFiori/database/issues/136)


### Bug Fixes

* **mysql:** cast PHP booleans to int for bit(1) column bindings ([480e659](https://github.com/WebFiori/database/commit/480e659bacaeaa27e9e5a58b8103697cab5f7513)), closes [#135](https://github.com/WebFiori/database/issues/135)
* **mysql:** cast PHP booleans to int for bit(1) column bindings ([d19c735](https://github.com/WebFiori/database/commit/d19c735c09a23fbf5b928d6573cf3de858f27152)), closes [#135](https://github.com/WebFiori/database/issues/135)

## [2.0.3](https://github.com/WebFiori/database/compare/v2.0.2...v2.0.3) (2026-04-28)


### Bug Fixes

* Data Types Mapping ([a8ec169](https://github.com/WebFiori/database/commit/a8ec1692025e7a245ba6102fbf8bc841721abac3)), closes [#132](https://github.com/WebFiori/database/issues/132)

## [2.0.2](https://github.com/WebFiori/database/compare/v2.0.1...v2.0.2) (2026-04-13)


### Miscellaneous Chores

* Update composer.json ([ee4669e](https://github.com/WebFiori/database/commit/ee4669e1fa381906721fb46db826a6fd4c8a1204))

## [2.0.1](https://github.com/WebFiori/database/compare/v2.0.0...v2.0.1) (2026-02-10)


### Features

* Added `getSupportedDataTypes` ([7ec96e3](https://github.com/WebFiori/database/commit/7ec96e3e4979d9cc5f0908daee0cd65a4f116e89))


### Miscellaneous Chores

* Merge pull request [#126](https://github.com/WebFiori/database/issues/126) from WebFiori/dev ([c1af0df](https://github.com/WebFiori/database/commit/c1af0df6aa033c1105e607ef169f555e681d3fb3))
* Run CS Fixer ([3eec245](https://github.com/WebFiori/database/commit/3eec2458b45695af3b95d0667b29f8aa7511c806))
* Run CS Fixer ([a9c7596](https://github.com/WebFiori/database/commit/a9c7596b7302da820b3abc36b654e3fdd8f24560))

## [2.0.0](https://github.com/WebFiori/database/compare/v1.2.0...v2.0.0) (2026-01-06)


### Features

* `saveAll` in Repo ([a4fd027](https://github.com/WebFiori/database/commit/a4fd02774df742d3f3774d2b571a183a6fb88260))
* Add Support for `DatabaseChangeGenerator` ([4ebe09d](https://github.com/WebFiori/database/commit/4ebe09db88b3812e247e474b921674dda32943b9))
* Add Support for `DatabaseChangeResult` ([a5d4ef6](https://github.com/WebFiori/database/commit/a5d4ef6c4b74e19eb0679eba0a9ffb902af90b5d))
* Add Support for Dry Run ([699673a](https://github.com/WebFiori/database/commit/699673a8a0a6930ea52d6f7fc80c0c7e6e33f3b3))
* Add Support for Getting Connection Info Under Change ([dca1048](https://github.com/WebFiori/database/commit/dca1048d0281185e71eb48e70c7c3a3dd149f811))
* Attributes ([83c4a5c](https://github.com/WebFiori/database/commit/83c4a5cc76029b8ed7da6302f728bf025ef67af6))
* Batching of Migrations ([e5694e6](https://github.com/WebFiori/database/commit/e5694e635b3fc232eba9800ae610a8a7af14f459))
* Eager Loading ([6ce98b8](https://github.com/WebFiori/database/commit/6ce98b8629bb80cbc71a5e1ae2825dc2972d3c0a))
* Entity Generator ([dbde80c](https://github.com/WebFiori/database/commit/dbde80c0e7c89acfe94f7754a97af79c204bfaf8))
* Migration/Seeder Discovery ([aaae1f0](https://github.com/WebFiori/database/commit/aaae1f06b6abe26e9bce009ce5d3a663caa8b808))
* Repo ([ff78798](https://github.com/WebFiori/database/commit/ff787982ba3c9bc6d88eb4b2eb54700d429392ee))
* Wrap Transitions in Changes ([145ad9c](https://github.com/WebFiori/database/commit/145ad9c4f8a85ab99bdb4524cdd1c83b96a231a8))


### Bug Fixes

* Ignore if Migration Already Registered ([23c2a9b](https://github.com/WebFiori/database/commit/23c2a9ba4a496b6a86e5256cd3288b837877c2f5))
* Imports Correction ([bf09ed1](https://github.com/WebFiori/database/commit/bf09ed16149f4a7e75ac7d8fe3f22a79d5c8243e))
* **mysql:** Auto-Increment ([e56a1ba](https://github.com/WebFiori/database/commit/e56a1ba6f044327766a53049b6517b313a992840))
* Return Count of Deleted ([6414b6e](https://github.com/WebFiori/database/commit/6414b6e95b2bbbf1e544de6566c51776015229af))


### Miscellaneous Chores

* Exclude Examples from Scan ([45fc84a](https://github.com/WebFiori/database/commit/45fc84a33a28eccef4ab67efeee770289703f571))
* Merge pull request [#119](https://github.com/WebFiori/database/issues/119) from WebFiori/feat-attributes ([f49786d](https://github.com/WebFiori/database/commit/f49786d924cf6bcf8057dc6826070733608bf5a7))
* Merge pull request [#120](https://github.com/WebFiori/database/issues/120) from WebFiori/docs ([8c329ad](https://github.com/WebFiori/database/commit/8c329adf88e16966e29a75228e29d296492d8312))
* Merge pull request [#121](https://github.com/WebFiori/database/issues/121) from WebFiori/dev ([9d71dd9](https://github.com/WebFiori/database/commit/9d71dd962ed8c0227c2fa2503efb34d3e4c6dd78))
* Merge pull request [#122](https://github.com/WebFiori/database/issues/122) from WebFiori/docs ([526655e](https://github.com/WebFiori/database/commit/526655eb9a86770c4a3337692acd0b5dfe7c26bf))
* Merge pull request [#124](https://github.com/WebFiori/database/issues/124) from WebFiori/feat-eager-load ([aa9de3e](https://github.com/WebFiori/database/commit/aa9de3e45fe258c2b2159c0eec1b4444c6204ab5))
* Merge pull request [#125](https://github.com/WebFiori/database/issues/125) from WebFiori/dev ([e43de85](https://github.com/WebFiori/database/commit/e43de85b005d7982591909281e732e48f4767588))
* Run CS Fixer ([74d3ffd](https://github.com/WebFiori/database/commit/74d3ffd8da5f3c55f08d7adce731fafdf81c8a72))
* Updated License ([ed99655](https://github.com/WebFiori/database/commit/ed99655cf0542d7be8d2bcfb92147e708cf7015e))
* Updated License Headers ([2803828](https://github.com/WebFiori/database/commit/2803828fba506fe80b6e08a286ca4fbc6424234b))
* Updated License Headers ([a7fc0ce](https://github.com/WebFiori/database/commit/a7fc0ce3c9ffbd18034eb7691a8eaeb6d4762d38))

## [1.2.0](https://github.com/WebFiori/database/compare/v1.1.0...v1.2.0) (2025-11-05)


### Features

* mult-results ([3c275de](https://github.com/WebFiori/database/commit/3c275de161ef4444a1242f9916d5a74a0ef2d68b))
* Multi Result Set ([8e0e4ab](https://github.com/WebFiori/database/commit/8e0e4aba4388db782b71fad95515d250b1791426))
* Multi Result Set ([8fecbf8](https://github.com/WebFiori/database/commit/8fecbf8af0d037abe75fa47f08d5140b0e4a213b))
* Parameterized Raw ([963743b](https://github.com/WebFiori/database/commit/963743bc52281948f8f88dde3c30b617fbd3535b))
* Parameterized Raw ([098285e](https://github.com/WebFiori/database/commit/098285e04e642b0b797040f7e016459393a14c3e))
* Result Set Even for Other Type of Queries ([14e9e5c](https://github.com/WebFiori/database/commit/14e9e5c20e5f6bf23b4ba050812386a2041e7d29))
* Result Set Even for Other Type of Queries ([91551c7](https://github.com/WebFiori/database/commit/91551c70abbc9c7436344cf2a0e0aa0e233c3c42))


### Miscellaneous Chores

* Configurations Update ([3705e5d](https://github.com/WebFiori/database/commit/3705e5d97782fb3318c665cff289c433c65ad682))
* Merge pull request [#114](https://github.com/WebFiori/database/issues/114) from WebFiori/dev ([14e9e5c](https://github.com/WebFiori/database/commit/14e9e5c20e5f6bf23b4ba050812386a2041e7d29))
* Merge pull request [#115](https://github.com/WebFiori/database/issues/115) from WebFiori/dev ([8e0e4ab](https://github.com/WebFiori/database/commit/8e0e4aba4388db782b71fad95515d250b1791426))
* Merge pull request [#117](https://github.com/WebFiori/database/issues/117) from WebFiori/dev ([963743b](https://github.com/WebFiori/database/commit/963743bc52281948f8f88dde3c30b617fbd3535b))
* Merge pull request [#118](https://github.com/WebFiori/database/issues/118) from WebFiori/dev ([43192d2](https://github.com/WebFiori/database/commit/43192d2d0054168a677a713ae45c7b779cb5cb85))

## [1.1.0](https://github.com/WebFiori/database/compare/v1.0.0...v1.1.0) (2025-09-29)


### Miscellaneous Chores

* Release 1.1.0 ([6389f8b](https://github.com/WebFiori/database/commit/6389f8b762c1c56eb685fecd1c7328d0a1099fe6))

## [1.0.0](https://github.com/WebFiori/database/compare/v0.10.0...v1.0.0) (2025-09-23)


### Features

* Added on Error Callbacks ([354b37d](https://github.com/WebFiori/database/commit/354b37db7df25abb1e2493f04bc42cab1c57e0cf))
* Circular dependency detection ([5179245](https://github.com/WebFiori/database/commit/5179245cddc757d83533ca33cc574273c845bafe))
* Migrations + Seeder Implementation ([2ddb13e](https://github.com/WebFiori/database/commit/2ddb13e1c749058128478d61615a89f07f1e862a))
* Performance Monitoring ([3d4da5d](https://github.com/WebFiori/database/commit/3d4da5d051fdffe314d2554be36e9107de710391))


### Bug Fixes

* Few Fixes ([9a8e6b8](https://github.com/WebFiori/database/commit/9a8e6b84c6d45168791de5b7f5bc1f76d4263ad8))
* Fix to A Bug in Migrations Runner ([a10d572](https://github.com/WebFiori/database/commit/a10d5720ee505432aed8ea97f9b5e820203dd5d4))


### Miscellaneous Chores

* Added Dev Req ([828b3d2](https://github.com/WebFiori/database/commit/828b3d22335f55d9507abcfca8bfee9edc77f6c8))
* Added Release Job ([af41db1](https://github.com/WebFiori/database/commit/af41db1b294eccf294fa8387093609be5c1e2673))
* Moved Files ([a44fc44](https://github.com/WebFiori/database/commit/a44fc440567a5f9d8d0dbb2b4bad2683d285536d))
* Run CS Fixer ([4e64202](https://github.com/WebFiori/database/commit/4e642021d22b1842c441734e55a03b1cd0cb2e44))

## [0.10.0](https://github.com/WebFiori/database/compare/v0.9.2...v0.10.0) (2025-02-04)


### Features

* Added Support for Applying one Single Migration ([2b7ad1f](https://github.com/WebFiori/database/commit/2b7ad1f163b018b945e3aba8864ac9d2f59590fb))


### Bug Fixes

* Added a Fix for Checking if Connected or Not ([e7ec27c](https://github.com/WebFiori/database/commit/e7ec27c48d069a19fc1cb21f7acecbc39acea55b))

## [0.9.2](https://github.com/WebFiori/database/compare/v0.9.1...v0.9.2) (2025-02-02)


### Bug Fixes

* Fix to Recursion Bug ([264407b](https://github.com/WebFiori/database/commit/264407b5410e717f263f50c0ec28bd6a6ab1db20))

## [0.9.1](https://github.com/WebFiori/database/compare/v0.9.0...v0.9.1) (2025-01-27)


### Miscellaneous Chores

* Updated PHPUnit Config ([c5ece90](https://github.com/WebFiori/database/commit/c5ece9035211ad8f42b26d21922a8a4361bfa165))

## [0.8.12](https://github.com/WebFiori/database/compare/v0.8.11...v0.8.12) (2024-12-03)


### Bug Fixes

* Added Missing Error Code in Exception ([d8adc32](https://github.com/WebFiori/database/commit/d8adc321a6bfca7753f1c2539c391b5c12cd4795))

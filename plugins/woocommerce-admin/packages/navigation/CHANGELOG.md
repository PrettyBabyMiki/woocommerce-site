# 5.0.0

-   `getPersistedQuery` Add a filter for extensions to add a persisted query, `woocommerce_admin_persisted_queries`.

# 4.0.0

## Breaking Changes

-   decouples `wcSettings` from the package (#3294)
-   `getAdminLink` is no longer available from this package. It is exported on the `wcSettings` global via the woo-blocks plugin (v2.5 or WC 3.9) when enqueued via the `wc-settings` handle.

# 3.0.0

-   `getHistory` updated to reflect path parameters in url query.
-   `getNewPath` also updated to reflect path parameters in url query.
-   `stringifyQuery` method is no longer available, instead use `addQueryArgs` from `@wordpress/url` package.
-   Added a new `<Form />` component.
-   Stepper component: Add new `content` and `description` props.
-   Remove `getAdminLink()` and dependency on global settings object.

# 2.1.1

-   Update license to GPL-3.0-or-later

# 2.1.0

-   New method `getSearchWords` that extracts search words given a query object.
-   Bump dependency versions.

# 2.0.0

-   Replace `history` export with `getHistory` (allows for lazy-create of history)

# 1.1.0

-   Rename `getTimeRelatedQuery` to `getPersistedQuery`

# Upgrade guide

## 1.x to 2.x

2.x is a rewrite. The OData resource layer, the `saintsystems/odata-client` dependency and every authentication method
other than OAuth have been removed. Instead of mapping a class per web service you talk to the Business Central API
directly through the `Dynamics` client, and records come back as `Entity` objects.

There is no compatibility layer: code written against `BaseResource` will not run on 2.x.

### Requirements

| Requirement | 1.x            | 2.x   |
|-------------|----------------|-------|
| PHP         | 8.3 or higher  | 8.4 or higher |
| Laravel     | 12.0 or higher | 13.0 or higher |

### Authentication

Only **OAuth with client credentials** is supported now, the only method the Business Central cloud API accepts. NTLM
and basic authentication are gone, and so are `DYNAMICS_AUTH`, `DYNAMICS_USERNAME` and `DYNAMICS_PASSWORD`. The client
requests and caches its own access token, so there is nothing to schedule or refresh.

### Configuration

Publish the configuration file again - the shape changed too much to migrate key by key.

```shell
php artisan vendor:publish --provider="JustBetter\DynamicsClient\ServiceProvider" --tag=config --force
```

A connection is now built from a `base_url` and `token_url` template (`{tenant_id}`, `{environment}`, `{api}`,
`{company_id}`), a `parameters` array that fills those placeholders, and OAuth credentials (`client_id`,
`client_secret`, `scope`, `grant_type`). Map friendly names to company IDs in `companies` instead of hardcoding one.
It is validated when used, so a leftover 1.x connection fails with a `ValidationException` naming the missing keys.

### Querying

Talk to the `Dynamics` client directly instead of a resource class, and build filters with `QueryBuilder`, which now
only returns the OData parameters - hand them to `entities()`, `lazyEntities()`, `get()` or `lazy()`.

> [!IMPORTANT]
> 2.x pages with `$top` and `$skip` and does not add an `$orderby` for you. Business Central does not guarantee a
> stable order, so pass one yourself or records can be repeated or skipped between pages.

Field names follow the API, not the page: the v2.0 endpoints use camelCase (`displayName`, `city`) where the OData web
services of 1.x used the page's own names (`Name`, `City`).

### ETags and `If-Match`

There is no `force` flag anymore. The `If-Match` header is decided per request: a header you set yourself wins,
otherwise the ETag of the entity scoped with `entity()` is used, otherwise `If-Match: *` is sent. So an unscoped
`patch()`, `put()` or `delete()` overwrites regardless of the record's version - scope the call to the entity you read
if you want a conditional write that fails with a 412 when it changed since.

### Error handling

The client no longer inspects status codes. Every verb returns the plain `Illuminate\Http\Client\Response`, and you
decide what a status means, or call `->throw()` for an exception. Connection timeouts still throw
`Illuminate\Http\Client\ConnectionException` and dispatch `DynamicsTimeoutEvent`.

### Queued entities

`Entity` is a plain data object now, so a queued one is restored exactly as it was queued - including a since-stale
ETag. Pass identifiers to your jobs and read the record inside `handle()` instead of queueing the entity itself.

### Testing

`Dynamics::fake()` configures a `default` connection with placeholder credentials and a `dynamics/` base URL, and
stubs the OAuth token request since the client authenticates itself. Only the API endpoints you call still need their
own fake:

```php
Dynamics::fake();

Http::fake([
    'dynamics/items*' => Http::response(['value' => [/* … */]]),
]);
```

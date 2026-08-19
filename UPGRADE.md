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

1.x supported NTLM, basic authentication and OAuth. 2.x only supports **OAuth with client credentials**, which is the
only method the Business Central cloud API accepts.

- `DYNAMICS_AUTH`, `DYNAMICS_USERNAME` and `DYNAMICS_PASSWORD` are gone. Remove them from your `.env`.
- The client requests and caches the access token itself, so there is nothing to schedule or refresh.

#### `redirect_uri` became a derived `token_url`

1.x asked for the token endpoint under the name `redirect_uri`, which had to be filled with the full
`https://login.microsoftonline.com/<tenant>/oauth2/v2.0/token` URL. 2.x has a `token_url` template that is resolved
with the same parameters as the API URL, so the tenant is configured once:

```php
// 1.x
'oauth' => [
    'redirect_uri' => env('DYNAMICS_OAUTH_REDIRECT_URI'), // https://login.microsoftonline.com/<tenant>/oauth2/v2.0/token
],

// 2.x
'parameters' => [
    'tenant_id' => env('DYNAMICS_TENANT_ID'),
],
'token_url' => env('DYNAMICS_OAUTH_TOKEN_URL', 'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token'),
```

Replace `DYNAMICS_OAUTH_REDIRECT_URI` with `DYNAMICS_TENANT_ID`. Only set `DYNAMICS_OAUTH_TOKEN_URL` when your token
endpoint is not the standard Microsoft one.

### Removed classes

| 1.x                                                | 2.x                                                                            |
|----------------------------------------------------|--------------------------------------------------------------------------------|
| `OData\BaseResource`, `OData\Resource`             | `Client\Dynamics` for the requests, `Data\Entity` for the records               |
| `OData\Pages\*` (`Customer`, `Item`, …)            | No replacement — pass the endpoint as a string, e.g. `entities('customers')`     |
| `Query\QueryBuilder` (returned resources)          | `Query\QueryBuilder` — same name, but it now builds an OData parameter array     |
| `Client\ClientFactory`, `Client\ClientHttpProvider`| `Actions\BuildRequest` (`Contracts\BuildsRequest`)                              |
| `Contracts\ClientFactoryContract`                  | `Contracts\BuildsRequest` and `Contracts\AuthenticatesRequest`                   |
| `Actions\ResolveTokenData`                         | `Actions\OAuth\RequestAccessToken` (`Contracts\OAuth\RequestsAccessToken`)       |
| `Concerns\HasData`                                 | `Data\Entity`, which extends `Illuminate\Support\Fluent`                         |
| `Concerns\HasKeys`, `$primaryKey`                  | `Entity::id()` — the API identifies every record by its `id`                     |
| `Concerns\HasCasts`, `$casts`                      | No replacement — attributes are the decoded JSON values                          |
| `Concerns\ValidatesData`                           | `Data\Data::validate()` with a `$rules` array                                    |
| `Concerns\CanBeSerialized`                         | No replacement — see [Queued entities](#queued-entities)                         |
| `Exceptions\NotFoundException`                     | The `Response` of the HTTP client, see [Error handling](#error-handling)          |
| `Exceptions\ModifiedException`                     | The `Response` of the HTTP client, see [Error handling](#error-handling)          |
| `Exceptions\UnreachableException`                  | `Exceptions\UnavailableException`, which already existed for the same purpose     |
| `Commands\TestConnection`                          | `Commands\ConnectionCommand` — the `dynamics:connect` signature is unchanged      |

`Exceptions\DynamicsException` and `Exceptions\UnavailableException` remain, but `DynamicsException` no longer carries
the request and response: `setRequest()`, `setResponse()`, `getRequest()` and `getResponse()` are gone.

### Removed configuration keys

Publish the configuration file again — the shape changed too much to migrate key by key.

```shell
php artisan vendor:publish --provider="JustBetter\DynamicsClient\ServiceProvider" --tag=config --force
```

| 1.x                        | 2.x                                                                       |
|----------------------------|---------------------------------------------------------------------------|
| `resources`                | Removed — the endpoint is an argument, not a mapping                       |
| `connections.*.base_url`   | `base_url`, now a template with `{tenant_id}`, `{environment}`, `{api}` and `{company_id}` |
| `connections.*.version`    | Removed — only the JSON API is supported; the API route is the `api` parameter |
| `connections.*.company`    | `parameters.company_id`, or a name in the `companies` map                  |
| `connections.*.uuid`       | `parameters.company_id`                                                    |
| `connections.*.auth`       | Removed — OAuth only                                                       |
| `connections.*.username`   | Removed                                                                    |
| `connections.*.password`   | Removed                                                                    |
| `connections.*.oauth.client_id`     | `connections.*.client_id`                                         |
| `connections.*.oauth.client_secret` | `connections.*.client_secret`                                     |
| `connections.*.oauth.redirect_uri`  | `connections.*.token_url`                                         |
| `connections.*.oauth.scope`         | `connections.*.scope`                                             |
| `connections.*.oauth.grant_type`    | `connections.*.grant_type`                                        |
| `connections.*.options.connect_timeout` | `connections.*.connect_timeout`, with `connections.*.timeout` for the request timeout |

The `availability` block and `page_size` are unchanged. `DYNAMICS_TIMEOUT` now sets the request timeout; the connect
timeout has its own `DYNAMICS_CONNECT_TIMEOUT`.

A connection is validated when it is used, so a leftover 1.x connection fails with a `ValidationException` naming the
missing keys rather than a broken request.

### Querying

`BaseResource::query()` is replaced by the `Dynamics` client plus a `QueryBuilder`. The builder no longer executes
anything: `get()` returns the OData parameters, which you hand to `entities()`, `lazyEntities()`, `get()` or `lazy()`.

```php
// 1.x
$customers = Customer::query()
    ->where('City', '=', 'Alkmaar')
    ->get();

// 2.x
$customers = app(Dynamics::class)->entities('customers', QueryBuilder::make()
    ->where('city', 'Alkmaar')
    ->get());
```

The same change applies to `lazy()`:

```php
// 1.x
Customer::query()->where('City', '=', 'Alkmaar')->lazy()->each(...);

// 2.x
app(Dynamics::class)
    ->lazyEntities('customers', QueryBuilder::make()->where('city', 'Alkmaar')->orderBy('id')->get())
    ->each(...);
```

> [!IMPORTANT]
> 2.x pages with `$top` and `$skip` and does not add an `$orderby` for you. Business Central does not guarantee a
> stable order, so pass one yourself or records can be repeated or skipped between pages.

Field names follow the API, not the page: the v2.0 endpoints use camelCase (`displayName`, `city`) where the OData web
services of 1.x used the page's own names (`Name`, `City`).

### ETags and `If-Match`

1.x read `@odata.etag` off the resource and sent it as `If-Match` on every write, with `update(..., force: true)` and
`delete(force: true)` to send `*` instead. A missing ETag was a fatal error, and a 412 became a `ModifiedException`.

2.x has no `force` flag. The header is decided per request:

1. An `If-Match` header you set yourself wins.
2. Otherwise the ETag of the entity the call is scoped to with `entity()` is used.
3. Otherwise `If-Match: *` is sent.

So an unscoped `patch()`, `put()` or `delete()` **overwrites regardless of the record's version**, where 1.x would
have refused without an ETag. Scope the call to the entity you read to keep the 1.x behaviour:

```php
// Conditional: fails with a 412 when the record changed since it was read.
$dynamics->entity($customer)->update(['displayName' => 'Jane Doe']);

// Unconditional: If-Match: * is sent.
$dynamics->patch('customers('.$customer->id().')', ['displayName' => 'Jane Doe']);

// Explicit: narrow it yourself.
$dynamics->header('If-Match', $etag)->patch('customers('.$id.')', ['displayName' => 'Jane Doe']);
```

### Error handling

The client no longer inspects status codes, so `NotFoundException` and `ModifiedException` are gone. Every verb
returns the `Illuminate\Http\Client\Response` of Laravel's HTTP client and you decide what a status means:

```php
// 1.x
try {
    $customer = Customer::query()->findOrFail('1000');
} catch (NotFoundException $exception) {
    //
}

try {
    $customer->update(['Name' => 'John Doe']);
} catch (ModifiedException $exception) {
    //
}

// 2.x
$response = $dynamics->get('customers(::id::)');

if ($response->notFound()) {
    //
}

$response = $dynamics->entity($customer)->update(['displayName' => 'John Doe']);

if ($response->status() === 412) {
    //
}
```

Call `->throw()` when you want an exception; it throws `Illuminate\Http\Client\RequestException`. Connection timeouts
still throw `Illuminate\Http\Client\ConnectionException` and still dispatch `DynamicsTimeoutEvent`, so the availability
layer keeps working as it did.

The `render()` method that made a 404 return an empty response is gone with `NotFoundException`. Handle a missing
record where you read it.

### Before and after

#### Reading a record

```php
// 1.x
use JustBetter\DynamicsClient\OData\Pages\Customer;

$customer = Customer::query()->findOrFail('1000');

$name = $customer['Name'];
```

```php
// 2.x
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Data\Entity;

$response = app(Dynamics::class)->get('customers(::id::)')->throw();

$customer = Entity::from($response, ['endpoint' => 'customers']);

$name = $customer->displayName;
```

#### Creating a record

```php
// 1.x
$customer = Customer::new()->create([
    'Name' => 'John Doe',
]);
```

```php
// 2.x
$response = app(Dynamics::class)->post('customers', [
    'displayName' => 'John Doe',
])->throw();

$customer = Entity::from($response, ['endpoint' => 'customers']);
```

#### Updating a record

```php
// 1.x
$customer = Customer::query()->findOrFail('1000');

$customer->update([
    'Name' => 'Jane Doe',
]);
```

```php
// 2.x
$dynamics = app(Dynamics::class);

$customer = $dynamics->entities('customers')->firstOrFail();

$customer->displayName = 'Jane Doe';

if ($customer->isDirty()) {
    $dynamics->entity($customer)->update();
}
```

`update()` without arguments sends the changed attributes only, and throws a `DynamicsException` when there are none —
hence the `isDirty()` guard when you update inside a loop. `refresh()` is gone: pass the response to `sync()` if you
want the entity to reflect what the server stored.

### Queued entities

1.x resources used the `CanBeSerialized` trait: queueing one stored its keys, and unserializing it fetched the record
again, so a job always worked with fresh data.

`Entity` has no such behaviour. It is a plain data object, so a queued `Entity` is restored **exactly as it was
queued** — including an ETag that may be outdated by the time the job runs. A conditional write then fails with a 412,
and an unconditional one overwrites changes made in the meantime.

Pass identifiers to your jobs and read the record inside `handle()`:

```php
// Do not do this: the entity, and its ETag, are frozen at dispatch time.
UpdateCustomer::dispatch($customer);

// Do this instead.
UpdateCustomer::dispatch($customer->id());

class UpdateCustomer implements ShouldQueue
{
    public function __construct(protected string $id) {}

    public function handle(Dynamics $dynamics): void
    {
        $response = $dynamics->get('customers('.$this->id.')')->throw();

        $customer = Entity::from($response, ['endpoint' => 'customers']);

        $customer->blocked = 'All';

        if ($customer->isDirty()) {
            $dynamics->entity($customer)->update();
        }
    }
}
```

### Testing

`BaseResource::fake()` is replaced by `Dynamics::fake()`, which configures a `default` connection with placeholder
credentials. Fake the token endpoint alongside the API, since the client authenticates itself:

```php
// 1.x
Item::fake();

Http::fake([
    'dynamics/ODataV4/Company(\'default\')/Item?$top=1' => Http::response(['value' => [/* … */]]),
]);

// 2.x
Dynamics::fake();

Http::fake([
    'https://login.microsoftonline.com/::tenant-id::/oauth2/v2.0/token' => Http::response([
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'access_token' => '::access-token::',
    ]),
    'https://api.businesscentral.dynamics.com/v2.0/::tenant-id::/::environment::/api/v2.0/companies(::company-id::)/items*' => Http::response([
        'value' => [/* … */],
    ]),
]);
```

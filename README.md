# Matryoshka

[![Build Status](https://travis-ci.org/iFixit/Matryoshka.png?branch=master)](https://travis-ci.org/iFixit/Matryoshka)

Matryoshka is a caching library for PHP built around nesting components like [Russian nesting dolls].

[Russian nesting dolls]: http://en.wikipedia.org/wiki/Matryoshka_doll

## Motivation

The [Memcache] and [Memcached] PHP client libraries offer fairly low level access to [memcached servers].
Matryoshka adds convenience functions to simplify common operations that aren't covered by the client libraries.
Most of the functionality is provided by nesting `Backend`s.
For example, prefixing cache keys is accomplished by nesting an existing `Backend` with a `Prefix` backend.
This philosophy results in very modular components that are easy to swap in and out and simplify testing.

This concept is used to support key prefixing, disabling `get`s/`set`s/`delete`s, defining cache fallbacks in a hierarchy, storing values in clearable scope, and recording statistics.

[Memcache]: http://php.net/memcache
[Memcached]: http://php.net/memcached
[memcached servers]: http://memcached.org

## Backends

### Enable

Disables `get`, `set`, or `delete` operations.

```php
$cache = new Matryoshka\Enable(...);
$cache->getsEnable = false;
$cache->get('key'); // Always results in a miss.
```

### Enable

Modifies all expiration times using a callback for the new value.

```php
$changeFunc = function($expiration) {
   // Double all expiration times.
   return $expiration * 2;
};
$cache = new Matryoshka\ExpirationChange($backend, $changeFunc);
$cache->set('key', 'value', 10); // Results in an expiration time of 20.
```

### Hierarchy

Sets caches in a hierarchy to prefer faster caches that get filled in by slower caches.

```php
$cache = new Matryoshka\Hierarchy([
   new Matryoshka\Ephemeral(),
   Matryoshka\Memcache::create(new Memcache('localhost')),
   Matryoshka\Memcache::create(new Memcache($cacheServers)),
]);

// This misses the first two caches (array and local memcached) but hits the
// final cache. The retrieved value is then set in the local memcache as well
// as the memory array so subsequent requests can be fulfilled faster.
$value = $cache->getAndSet('key', function() {
   return 'value';
}, 3600);
// This is retrieved from the memory array without going all the way to
// Memcache.
$value = $cache->getAndSet('key', function() {
   return 'value';
}, 3600);
```

### KeyShorten

Ensures that all keys are at most the specified length by shortening longer ones.

```php
$cache = new Matryoshka\KeyShorten(
   new Matryoshka\Ephemeral(),
   $maxLength = 50
);

// Gets converted to: `long_key_that_need2552e62135d11e8d4233e2a51868132e`
$cache->get("long_key_that_needs_to_be_shortened_by_just_a_little_bit");
```

### Local

Caches all values in a local array so subsequent requests for the same key can be fulfilled faster.
It's faster version of:

```php
$cache = new Matryoshka\Hierarchy([
   new Matryoshka\Ephemeral(),
   Matryoshka\Memcache::create(new Memcache('localhost'))
]);
```

### Memcache

Wraps the [Memcache] client library.

```php
$memcache = new Memcache();
$memcache->pconnect('localhost', 11211);
$cache = Matryoshka\Memcache::create($memcache);

$value = $cache->get('key');
```

### Ephemeral

Caches values in a local memory array that lasts the duration of the PHP process.

```php
$cache = new Matryoshka\Ephemeral();
$cache->set('key', 'value');
$value = $cache->get('key');
```

### Prefix

Prefixes all keys with a string.

```php
$cache = new Matryoshka\Prefix(new Matryoshka\Ephemeral(), 'prefix-');
// The key ends up being "prefix-key".
$cache->set('key', 'value');
$value = $cache->get('key');
```

### Scope

Caches values in a scope that can be deleted to invalidate all cache entries under the scope.

```php
$cache = new Matryoshka\Scope(new Matryoshka\Ephemeral(), 'scope');
$cache->set('key', 'value');
$value = $cache->get('key');
$cache->deleteScope();
// This results in a miss because the scope has been deleted.
$value = $cache->get('key');
```

### Stats

Records counts and timings for operations to be used for metrics.

```php
$cache = new Matryoshka\Stats(new Matryoshka\Ephemeral());
$cache->set('key', 'value');
$value = $cache->get('key');
var_dump($cache->getStats());
// array(
//    'get_count' => 1,
//    'get_time' => 0.007,
//    'set_count' => 1
//    'set_time' => 0.008,
//    ...
// )
```

## Convenience Functions

### getAndSet

Wrapper for `get()` and `set()` that uses a read-through callback to generate missed values.

```php
$cache = new Matryoshka\Ephemeral();
// Calls the provided callback if the key is not found and sets it in the cache
// before returning the value to the caller.
$value = $cache->getAndSet('key', function() {
   return 'value';
});
```

### getAndSetMultiple

Wrapper around `getMultiple()` that uses a callback to generate values in batch to populate the cache.


```php
$cache = new Matryoshka\Ephemeral();
$keys = [
   'key1' => 'id1',
   'key2' => 'id2'
];
// Calls the provided callback for any missed keys so the missing values can be
// generated and set before returning them to the caller. The values are
// returned in the same order as the provided keys.
$values = $cache->getAndSetMultiple($keys, function($missing) {
   // Use the id's to fill in the missing values.
   foreach ($missing as $key => $id) {
      if ($id == 'id1') {
         $value = 'value1';
      } else if ($id == 'id2') {
         $value = 'value2';
      }

      $missing[$key] = $value;
   }

   // Return the new values to be cached and merged with the hits.
   return $missing;
});
```

## License

    The MIT License (MIT)

    Copyright (c) 2014 iFixit

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the Software), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED AS IS, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.

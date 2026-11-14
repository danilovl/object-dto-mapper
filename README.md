[![phpunit](https://github.com/danilovl/object-dto-mapper/actions/workflows/phpunit.yml/badge.svg)](https://github.com/danilovl/object-dto-mapper/actions/workflows/phpunit.yml)
[![downloads](https://img.shields.io/packagist/dt/danilovl/object-dto-mapper)](https://packagist.org/packages/danilovl/object-dto-mapper)
[![latest Stable Version](https://img.shields.io/packagist/v/danilovl/object-dto-mapper)](https://packagist.org/packages/danilovl/object-dto-mapper)
[![license](https://img.shields.io/packagist/l/danilovl/object-dto-mapper)](https://packagist.org/packages/danilovl/object-dto-mapper)

# Object to DTO mapper

## Description
A lightweight mapper that converts an object (Entity/model) to a DTO based on the DTO constructor signature.

Mapping is controlled via the `#[Map]` attribute on DTO constructor parameters / properties.

It supports conditional mapping (`if`), value transformations (`transform`), collections, nested objects,
enums, dates and filtering by Symfony Serializer `#[Groups]` — and it is safe by default: entities never leak into a DTO unmapped.

### Key features
- Map by matching a property name or from any property path via `Map(source)` (`profile.address.city`, `settings[theme]`)
- Conditional mapping: bool, callable, closure or container service (`Map(if)` / `Map(ifContainer)`)
- Transformations: callable, closure or container service (`Map(transform)` / `Map(transformContainer)`)
- Nested objects are mapped recursively based on the constructor parameter type (union types supported)
- Collections (arrays, `Traversable`, generators, Doctrine collections) are mapped item by item via `Map(target)`
- Automatic conversion of `DateTimeInterface` and enums (see [Type conversion](#type-conversion))
- Filter DTO properties by groups (`allowGroups`/`ignoreGroups`), nested DTOs included
- Protection against leaking unmapped objects, circular references and too deep graphs
- Every error is a `MappingException` with the property path, e.g. `(path: "posts[3].author.name")`
- Metadata (reflection, attributes, resolved callables) is built once per DTO class and cached

## Requirements
- PHP 8.5+
- symfony/property-access 8.0+
- symfony/dependency-injection, symfony/config, symfony/http-kernel, symfony/yaml 8.0+
- psr/container 1.1+ or 2.0+
- symfony/serializer 8.0+ (optional, only for `#[Groups]` filtering)

## Installation

```bash
composer require danilovl/object-dto-mapper
```

With Symfony Flex the bundle is registered automatically. Otherwise add it to `config/bundles.php`:

```php
// config/bundles.php

return [
    // ...
    Danilovl\ObjectDtoMapper\ObjectDtoMapperBundle::class => ['all' => true]
];
```

That is all: `ObjectToDtoMapperInterface` can be injected right away, no service configuration is needed.

If you want to filter by groups:

```bash
composer require symfony/serializer
```

## Quick start

```php
<?php declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class UserEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $name;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $password;

    #[ORM\Column(type: Types::INTEGER)]
    public int $age;

    #[ORM\Column(type: Types::STRING, enumType: UserStatus::class)]
    public UserStatus $status;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $registeredAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $email = null;
}
```

```php
<?php declare(strict_types=1);

namespace App\Dto;

final readonly class UserDto
{
    public function __construct(
        public string $name,
        public int $age,
        public string $status,          // backed enum -> its value
        public ?string $registeredAt,   // DateTimeInterface -> ATOM string
        public ?string $email,
    ) {}
}
```

```php
<?php declare(strict_types=1);

namespace App\Controller;

use App\Dto\UserDto;
use App\Entity\UserEntity;
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class UserController
{
    public function __construct(private readonly ObjectToDtoMapperInterface $mapper) {}

    #[Route('/api/users/{id}', name: 'api_user', methods: ['GET'])]
    public function show(UserEntity $user): JsonResponse
    {
        return new JsonResponse($this->mapper->map($user, UserDto::class));
    }

    #[Route('/api/users', name: 'api_users', methods: ['GET'])]
    public function list(UserRepository $repository): JsonResponse
    {
        return new JsonResponse($this->mapper->mapCollection($repository->findAll(), UserDto::class));
    }
}
```

## Mapper API

```php
interface ObjectToDtoMapperInterface
{
    public function map(object $source, string $target, array $allowGroups = [], array $ignoreGroups = []): object;

    public function mapCollection(iterable $collection, string $target, array $allowGroups = [], array $ignoreGroups = []): array;
}
```

Constructor options of `ObjectToDtoMapper`:

| Option | Default | Description |
|---|---|---|
| `$container` | `null` | PSR container (preferably a service locator) for `Map(ifContainer)`, `Map(transformContainer)` and invokable class-strings |
| `$propertyAccessor` | new accessor | Inject the cached `property_accessor` service in Symfony |
| `$allowObjectPassthrough` | `false` | Allow unmapped objects inside `array`/`mixed`/`object` parameters (see [Security](#security-unmapped-objects)) |
| `$maxDepth` | `32` | Maximum nesting depth of mapped objects |

## Symfony integration

The bundle registers everything automatically:

- `ObjectToDtoMapper` service with the `ObjectToDtoMapperInterface` alias (autowiring works out of the box);
- the cached `property_accessor` service of the framework is injected when available;
- every service implementing `MapIfCallableInterface` or `MapTransformCallableInterface` is found by a compiler pass
  and is available to the mapper by its service id (class name) through a service locator — with or without
  `autoconfigure`, private services included; no tags are needed and the whole `service_container` is never exposed.

A handler only has to be a registered service (e.g. via the default `App\` resource of `services.yaml`)
and implement the interface:

```php
final class EmailGuardService implements MapIfCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): bool
    {
        return !empty($value);
    }
}

final class UserDto
{
    public function __construct(
        #[Map(ifContainer: EmailGuardService::class)]
        public ?string $email = null,
    ) {}
}
```

The service is looked up by the id used in the attribute, so for a service registered under a custom id
use that id: `#[Map(ifContainer: 'app.email_guard')]`.

### Configuration (optional)

```yaml
# config/packages/danilovl_object_dto_mapper.yaml
danilovl_object_dto_mapper:
    allow_object_passthrough: false # see "Security: unmapped objects"
    max_depth: 32                   # maximum nesting depth of mapped objects
```

### Without Symfony

The mapper can be created manually; pass any PSR-11 container for container handlers:

```php
$mapper = new ObjectToDtoMapper(container: $psrContainer);
```

## Where to put `#[Map]`

The mapper reads the target constructor. For every constructor parameter the `#[Map]` attribute is looked up:

1. on the constructor-promoted property (the usual case);
2. on a regular (non-promoted) constructor parameter;
3. on a class property with the same name as the parameter.

```php
final class ArticleDto
{
    #[Map(source: 'headline')]
    public string $title;

    public string $slug;

    public function __construct(string $title, #[Map(source: 'name')] string $slug)
    {
        $this->title = $title;
        $this->slug = mb_strtolower($slug);
    }
}
```

Inherited constructors (also with `private` promoted properties) and variadic parameters (`string ...$tags`) are supported.
The target must be a concrete class with a public constructor.

## #[Map] attribute

```php
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
readonly class Map
{
    public function __construct(
        public ?string $target = null,             // DTO class for collection items / nested object
        public ?string $source = null,             // property path in the source object
        public mixed $if = null,                   // bool | callable | Closure
        public ?string $ifContainer = null,        // service id (MapIfCallableInterface)
        public mixed $transform = null,            // callable | Closure
        public ?string $transformContainer = null  // service id (MapTransformCallableInterface)
    ) {}
}
```

Configuration is validated when the attribute is instantiated:
- `if` and `ifContainer` cannot be used together — `MappingIfException`
- `transform` and `transformContainer` cannot be used together — `MappingTransformException`
- `ifContainer`/`transformContainer` must be non-empty strings
- `if` must be bool, string, array or Closure; `transform` must be string, array or Closure

### Supported callable forms for `if` and `transform`

| Form | Example |
|---|---|
| Closure (PHP 8.5) | `#[Map(transform: static function (string $v): string { return trim($v); })]` |
| First-class callable (PHP 8.5) | `#[Map(transform: mb_strtoupper(...))]` |
| Function name | `#[Map(transform: 'trim')]` |
| Static method string | `#[Map(if: EmailGuard::class . '::notEmpty')]` |
| Static method array | `#[Map(if: [EmailGuard::class, 'notEmpty'])]` |
| Invokable class | `#[Map(transform: Uppercase::class)]` |

An invokable class-string is taken from the container when the container has it, otherwise it is instantiated
with `new` (it must not have required constructor arguments). Resolved callables are cached per DTO class.

Handlers receive up to three arguments: `(mixed $value, object $source, string $target)`, where `$target` is the DTO class
being created. A handler declaring fewer parameters receives fewer arguments; built-in PHP functions (`trim`, `strlen`, …)
receive only the value.

## Mapping pipeline

For every constructor parameter the mapper performs these steps in this order:

1. **Groups** — if the property is excluded by `allowGroups`/`ignoreGroups`, the source is not touched at all (getters are not called).
2. **`Map(if: false)`** — skips the property without reading the source.
3. **Read** the value once from `source` (property name by default) using Symfony PropertyAccess (public property, getter `getX()`/`isX()`/`hasX()`, array index, nested path).
4. **Condition** `if`/`ifContainer` — the result is cast to bool.
5. **Transform** `transform`/`transformContainer`.
6. **Null handling** and **type conversion** / nested mapping.

### Skipped or unreadable values

When a property is excluded by groups, skipped by a condition or the source path cannot be read
(missing or private property, uninitialized typed property, `null` in the middle of a path):

1. the constructor default value is used, if any;
2. otherwise `null`, if the parameter is nullable;
3. otherwise an exception is thrown:

| Reason | Exception |
|---|---|
| excluded by `ignoreGroups` / `allowGroups` | `RuntimeException` (`... is restricted by ignoreGroups filter`) |
| `if` / `ifContainer` returned false | `MappingIfException` (`... skipped by Map(if) condition`) |
| source path cannot be read | `NoSuchPropertyException` |

### Null values

When the read (or transformed) value is `null`:
- nullable parameter → `null` (an explicit `null` from the source wins over the default value);
- non-nullable parameter with a default value → the default value;
- otherwise → `RuntimeException` (`Non-nullable property "x" of "Dto" is null`).

Object default values (`public Options $options = new Options`) are created fresh for every DTO.

## Type conversion

| Source value | Parameter type | Result |
|---|---|---|
| object | class/interface the object already is an instance of | the object as is |
| `DateTimeInterface` | `string`, `mixed`, untyped | `DateTimeInterface::ATOM` string |
| `DateTimeInterface` | `DateTimeImmutable` / `DateTime` (or subclass) | converted via `createFromInterface()` |
| `BackedEnum` | `string` / `int` | `->value` (int-backed enum to `string` is cast) |
| `UnitEnum` | `string` | `->name` |
| enum | `mixed`, `object`, untyped | the enum as is |
| `int`/`string` | `BackedEnum` | `Enum::tryFrom()`; invalid value → `RuntimeException` |
| object | concrete DTO class (also inside a union type) | mapped recursively |
| object | interface / abstract class it does not implement | `RuntimeException` — use `Map(target)` |
| iterable | `array` / `iterable` with `Map(target)` | every item mapped to the target |
| `Traversable` | `array` / `iterable` without `Map(target)` | converted to array |
| scalar | scalar | passed as is (PHP coercion applies) |

## Usage examples

### 1) Map from another field or a nested path (source)
```php
final class UserDto
{
    public function __construct(
        #[Map(source: 'altName')]
        public string $name,
        #[Map(source: 'profile.address.city')]
        public ?string $city,          // null when profile or address is null
        #[Map(source: 'settings[theme]')]
        public string $theme = 'light',
    ) {}
}
```

### 2) Conditional mapping: simple flags
```php
final class DtoIfBool
{
    public function __construct(
        #[Map(if: true)]
        public string $name,
        #[Map(if: false)]
        public ?int $age = null,
    ) {}
}
```

### 3) Conditional mapping: callable or closure
```php
final class DtoIfCallable
{
    public function __construct(
        public string $name,
        #[Map(if: [EmailGuard::class, 'allowIfNotEmpty'])]
        public ?string $email = null,
        #[Map(if: static function (mixed $value, UserEntity $source): bool {
            return $source->isPhonePublic();
        })]
        public ?string $phone = null,
    ) {}
}

final class EmailGuard
{
    public static function allowIfNotEmpty(?string $value): bool
    {
        return !empty($value);
    }
}
```

### 4) Conditional mapping via container (ifContainer)
```php
final class DtoIfContainer
{
    public function __construct(
        public string $name,
        #[Map(ifContainer: EmailGuardService::class)]
        public ?string $email = null,
    ) {}
}

final class EmailGuardService implements MapIfCallableInterface
{
    public function __construct(private readonly Security $security) {}

    public function __invoke(mixed $value, object $source, string $target): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
```

### 5) Transform
```php
final class DtoTransform
{
    public function __construct(
        #[Map(transform: Uppercase::class)]
        public string $name,
        #[Map(transform: mb_strtolower(...))]
        public string $login,
        #[Map(transform: static function (?string $v): ?string {
            return $v === null ? null : mb_strtoupper(mb_trim($v));
        })]
        public ?string $email = null,
    ) {}
}

final class Uppercase
{
    public function __invoke(string $value): string
    {
        return mb_strtoupper($value);
    }
}
```

The value is passed to the transformer as is, including `null`: make the transformer null-safe for nullable sources.
The transformer result goes through the normal [type conversion](#type-conversion), so returning a `DateTimeInterface`
into a `string` parameter produces an ATOM string. A transformer result is trusted: it is not checked by the
unmapped-object protection.

### 6) Transform using a service (transformContainer)
```php
final class DtoTransformService
{
    public function __construct(
        #[Map(transformContainer: PriceFormatter::class)]
        public string $price,
    ) {}
}

final class PriceFormatter implements MapTransformCallableInterface
{
    public function __construct(private readonly NumberFormatter $formatter) {}

    public function __invoke(mixed $value, object $source, string $target): mixed
    {
        return $this->formatter->formatCurrency((float) $value, 'EUR');
    }
}
```

### 7) Collections
```php
final class OrderDto
{
    /**
     * @param OrderItemDto[] $items
     */
    public function __construct(
        #[Map(target: OrderItemDto::class)]
        public array $items,
    ) {}
}
```

- Works with arrays, `Traversable` (Doctrine `Collection`), generators.
- Integer keys are re-indexed into a list (a Doctrine collection after `removeElement()` still serializes as a JSON array); string keys are preserved.
- `null` items are kept as `null`; scalar items throw `RuntimeException`.
- Items that already are instances of the target are kept.
- `$mapper->mapCollection($entities, OrderItemDto::class)` maps a top-level collection.

### 8) Nested objects
If the parameter type is a concrete class, the source object is mapped recursively:
```php
final class UserDto
{
    public function __construct(
        public string $name,
        public ProfileDto $profile,
        public ?AddressDto $address,          // null stays null
        public AddressDto|string $location,   // union types are supported
    ) {}
}
```

For an interface or abstract parameter type specify the class with `Map(target)`:
```php
#[Map(target: PostalAddressDto::class)]
public AddressInterface $address,
```

### 9) Groups (allowGroups/ignoreGroups)
Mark DTO properties with `Symfony\Component\Serializer\Attribute\Groups`:
```php
final class UserDto
{
    public function __construct(
        #[Groups(['public'])]
        public string $name,
        #[Groups(['admin'])]
        public ?string $email = null,
        public ?int $age = null,           // no group
    ) {}
}

$mapper->map($user, UserDto::class, allowGroups: ['public']);                       // name
$mapper->map($user, UserDto::class, allowGroups: ['public', 'admin']);              // name, email
$mapper->map($user, UserDto::class, ignoreGroups: ['admin']);                       // name, age
$mapper->map($user, UserDto::class, allowGroups: ['public'], ignoreGroups: ['x']);  // name
```

Rules (same as Symfony Serializer):
- `allowGroups` not empty → only properties belonging to at least one of the groups are mapped; properties without groups are excluded.
- `ignoreGroups` → properties belonging to any of the groups are excluded; `ignoreGroups` wins over `allowGroups`.
- Groups are applied to nested DTOs and collection items as well.
- An excluded property gets its default value or `null`; a non-nullable property without default throws `RuntimeException`.

## Security: unmapped objects

An object that is not mapped to a DTO is never put into the DTO as is. Without this protection an entity inside
an `array`, `mixed`, `object` or untyped parameter would be serialized with all its public fields (password hash, tokens, …):

```php
final class UserListDto
{
    public function __construct(public array $users) {}  // no Map(target)
}

$mapper->map($group, UserListDto::class);
// UnmappedObjectException: Refusing to pass unmapped object of class "App\Entity\UserEntity" into property "users" ...
```

Allowed without mapping:
- scalars, arrays of scalars;
- `DateTimeInterface` and enums;
- an object whose class is **explicitly** declared as the parameter type (`public UserEntity $user`);
- the result of a transformer.

Fix the DTO by declaring a DTO type / `Map(target)` / `Map(transform)`, or disable the check globally with
`new ObjectToDtoMapper(allowObjectPassthrough: true)`.

## Recursion

- Circular references (`Author → posts → Post → author → Author`) throw `CircularReferenceException`
  with the path where the cycle was found. Break the cycle with a separate summary DTO, groups or `Map(if)`.
- The same object used in several non-cyclic places (one author of several posts) is fine.
- Graphs deeper than `maxDepth` (default 32) throw `MaxDepthException`.

## Exceptions

All exceptions extend `Danilovl\ObjectDtoMapper\Exception\MappingException` (which extends `\RuntimeException`),
so a single `catch (MappingException)` is enough. Messages contain the property path, e.g. `(path: "items[2].author.name")`.

| Exception | When |
|---|---|
| `NoSuchClassException` | target class or `Map(target)` class or invokable class does not exist |
| `NoSuchPropertyException` | source cannot be read for a non-nullable property without default |
| `MappingIfException` | invalid `if`/`ifContainer` definition, handler failed, non-nullable property skipped |
| `MappingTransformException` | invalid `transform`/`transformContainer` definition or handler failed |
| `UnmappedObjectException` | an unmapped object would be put into the DTO |
| `CircularReferenceException` | circular reference in the source graph |
| `MaxDepthException` | graph deeper than `maxDepth` |
| `RuntimeException` | invalid target class, null for non-nullable, group restriction, invalid enum value, type mismatch (`TypeError` is attached as previous) |

Exceptions thrown by `if`/`transform` handlers are wrapped into `MappingIfException`/`MappingTransformException`;
the original exception is available via `getPrevious()`.

## Upgrade notes (from previous versions)

- **`allowGroups` now works on its own.** Previously it was ignored unless `ignoreGroups` was also set, so
  `map($e, Dto::class, allowGroups: ['public'])` mapped every property.
- Unmapped objects inside `array`/`mixed`/`object`/untyped parameters now throw `UnmappedObjectException`
  (use `allowObjectPassthrough: true` to restore the old behaviour).
- Collections keep `null` items and string keys; scalar items throw instead of being silently dropped.
- A `null` source value for a non-nullable parameter with a default value now uses the default instead of throwing.
- A non-readable source for a non-nullable parameter without default throws `NoSuchPropertyException` instead of a `TypeError`.
- Errors in transform definitions throw `MappingTransformException` (previously `MappingIfException`).
- `#[Map]` targets properties and parameters only (`TARGET_CLASS` and `IS_REPEATABLE` were never used).
- `symfony/serializer` is optional, `psr/container` is now a declared dependency.
- New method `mapCollection()` in `ObjectToDtoMapperInterface`.
- The package is now a Symfony bundle (`ObjectDtoMapperBundle`): remove the manual service definition of the mapper
  (and the `$container: '@service_container'` argument) from your `services.yaml`.

## License
This package is open-sourced software licensed under the [MIT](https://opensource.org/licenses/MIT).

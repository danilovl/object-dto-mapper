[![phpunit](https://github.com/danilovl/object-dto-mapper/actions/workflows/phpunit.yml/badge.svg)](https://github.com/danilovl/object-dto-mapper/actions/workflows/phpunit.yml)
[![downloads](https://img.shields.io/packagist/dt/danilovl/object-dto-mapper)](https://packagist.org/packages/danilovl/object-dto-mapper)
[![latest Stable Version](https://img.shields.io/packagist/v/danilovl/object-dto-mapper)](https://packagist.org/packages/danilovl/object-dto-mapper)
[![license](https://img.shields.io/packagist/l/danilovl/object-dto-mapper)](https://packagist.org/packages/danilovl/object-dto-mapper)

# Object to DTO mapper

## Description
A lightweight mapper that converts an object (Entity/model) to a DTO based on the DTO constructor signature.

Mapping is controlled via the #[Map] attribute on DTO properties (constructor-promoted properties are supported).

It supports conditional mapping (if), value transformations (transform), collections and nested objects, as well as filtering by Symfony Serializer Groups.

### Key features
- Map by matching a property name or from another path via Map(source)
- Conditional mapping: bool, callable, or container service (Map(if) / Map(ifContainer))
- Transformations: callable/class or container service (Map(transform)/Map(transformContainer))
- Auto-format DateTimeInterface -> ATOM if no transform is specified
- Map collections into target DTO via Map(target)
- Nested objects are mapped recursively based on the constructor parameter type
- Filter DTO properties by groups (allowGroups/ignoreGroups) using Symfony\Component\Serializer\Attribute\Groups on DTO

## Requirements
- PHP 8.5+
- symfony/property-access 8.0+
- symfony/serializer 8.0+

## Installation

Install the package via Composer:

```bash
 composer require danilovl/object-dto-mapper
```

## Quick start

Example: we have an entity and a DTO.

```php
<?php declare(strict_types=1)

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
final class UserEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $name;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $gender;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $username;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $password;

    #[ORM\Column(type: Types::INTEGER)]
    public int $age;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $registeredAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $email = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    public array $roles;
}
```

```php
<?php declare(strict_types=1)

namespace App\DTO

final class UserDto
{
    public function __construct(
        public string $name,
        public int $age,
        public ?string $registeredAt,
        public ?string $email,
    ) {}
}
```

```php
<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserEntity;
use App\Dto\UserDto;
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class UserController
{
    public function __construct(private readonly ObjectToDtoMapperInterface $mapper) {}

    #[Route('/api/users/{id}', name: 'api_users', methods: ['GET'])]
    public function show(UserEntity $userEntity): JsonResponse
    {
        $dto = $this->mapper->map($userEntity, UserDto::class);

        return new JsonResponse($dto);
    }
}
```

## #[Map] attribute

The attribute is applied to DTO properties (typically constructor-promoted properties).

Signature:
```php
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class Map
{
    public function __construct(
        public ?string $target = null,
        public ?string $source = null,
        public mixed $if = null,              // bool | callable | string (see below)
        public ?string $ifContainer = null,   // service id in container
        public mixed $transform = null,       // callable | class-string | [class-string, method]
        public ?string $transformContainer = null // transformer service id
    ) { /* guard against mutually exclusive options */ }
}
```
Configuration constraints:
- You cannot set both if and ifContainer — MappingIfException will be thrown
- You cannot set both transform and transformContainer — MappingTransformException will be thrown

### Parameters
- source: path/property name in the source object (PropertyAccess is supported, e.g. "profile.email" or "["email"]")
- target: target DTO class for collection items. If the source value is iterable and a target is provided, each item will be mapped to that DTO
- if: mapping condition. Allowed forms:
    - true/false — simply include/exclude mapping
    - callable: static callback [Class, 'method'] or invokable class name. Various signatures supported: (value), (value, source), (value, source, target). Note: due to PHP attribute limitations, closures/objects cannot be passed directly in attributes
    - string: class name of an invokable class (must implement __invoke). Must return bool, otherwise MappingIfException is thrown
- ifContainer: string service id from the container. The service must be invokable (implement MapIfCallableInterface) and return bool
- transform: value transformation. Supported forms:
    - callable (class-string with __invoke), or array [ClassName, 'method']
    - Note: multiple transforms chain via array is not supported; use a single transformer that composes steps
- transformContainer: string transformer service id from the container (must be invokable: implement MapTransformCallableInterface)

### Defaults and errors
- If a source property is not readable, the default value from the DTO constructor is used, otherwise null
- If the if/ifContainer condition denies mapping:
    - use the default from constructor; if none — null for a nullable type
    - if the property is non-nullable and there is no default — MappingIfException is thrown
- If after all transforms the value is null, and the property is non-nullable — RuntimeException is thrown
- DateTimeInterface without transform is formatted to DateTimeInterface::ATOM
- Errors inside if/transform handlers result in MappingIfException/MappingTransformException with detailed messages

## Usage examples and combinations

### 1) Map from another field (source)
```php
final class DtoA 
{ 
    public function __construct(
        #[Map(source: 'altName')] 
        public string $name,
        public int $age,
        public ?string $email,
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
        public ?string $email = null,
    ) {}
}
```

### 3) Conditional mapping: callable
```php
final class DtoIfCallable 
{ 
    public function __construct(
        public string $name,
        public int $age,
        #[Map(if: [EmailGuard::class, 'allowIfNotEmpty'])] 
        public ?string $email = null,
    ) {} 
}

final class EmailGuard 
{
    public static function allowIfNotEmpty(?string $value): bool {
        return !empty($value);
    }
}
```

Other accepted forms: 'ClassName' (if the class is invokable) or [ClassName, 'method']. The return value must be bool. Note: closures and object instances cannot be used directly inside attributes.

### 4) Conditional mapping via container (ifContainer)

```php
final class DtoIfContainer 
{ 
    public function __construct(
        #[Map(ifContainer: EmailGuardService::class)] 
        public ?string $email = null,
        public string $name,
        public int $age,
    ) {} 
}

final class EmailGuardService implements \Danilovl\ObjectDtoMapper\Attribute\MapIfCallableInterface 
{
    public function __invoke(mixed $value, object $source, string $target): bool 
    { 
        return !empty($value); 
    }
}
```

### 5) Single value transform
```php
final class DtoTransformOne 
{ 
    public function __construct(
        #[Map(transform: Uppercase::class)] 
        public string $name,
        public int $age,
        public ?string $email,
    ) {} 
}

final class Uppercase 
{ 
    public function __invoke(string $v): string 
    { 
        return mb_strtoupper($v); 
    } 
}
```

### 6) Compose multiple steps inside a single transformer
```php
final class DtoTransformComposed 
{ 
    public function __construct(
        #[Map(transform: TrimUppercase::class)] 
        public ?string $email = null,
        public string $name,
        public int $age,
    ) {} 
}

final class TrimUppercase 
{ 
    public function __invoke(?string $v): ?string
     { 
        if ($v === null) { return null; }
        $v = trim($v);
        return mb_strtoupper($v);
    } 
}
```

### 7) Transform using a service from container (transformContainer)

```php
final class DtoTransformService 
{ 
    public function __construct(
        #[Map(transformContainer: UppercaseService::class)] 
        public string $name,
        public int $age,
        public ?string $email,
    ) {} 
}

final class UppercaseService implements \Danilovl\ObjectDtoMapper\Attribute\MapTransformCallableInterface 
{
    public function __invoke(mixed $value, object $source, string $target): mixed 
    {
        return mb_strtoupper((string) $value); 
    }
}
```

### 8) Collections + target
```php
final class EntityList
{
    public function __construct(public array $items) {}
}

final class ItemEntity
{
    public function __construct(public string $title) {}
}

final class ItemDto
{
    public function __construct(public string $title) {}
}

final class ListDto
{ 
    public function __construct(
        #[Map(target: ItemDto::class)] 
        public array $items,
    ) {} 
}

$mapper->map
    (new EntityList([new ItemEntity('A'), new ItemEntity('B')]), 
    ListDto::class
);
```

### 9) Nested objects
If the DTO constructor parameter type is an object, and the source value is an object, it will be mapped recursively:
```php
final class ProfileEntity
{
    public function __construct(public string $city) {}
}

final class ProfileDto
{
    public function __construct(public string $city) {}
}

final class UserEntity
{
    public function __construct(public string $name, public ProfileEntity $profile) {}
}

final class UserDto
{
    public function __construct(public string $name, public ProfileDto $profile) {}
}
```

### 10) Groups (allowGroups/ignoreGroups)
Mark DTO properties with Groups and then pass allowGroups and/or ignoreGroups to ObjectToDtoMapper::map:
```php
final class DtoWithGroups 
{ 
    public function __construct(
        #[Groups(['public'])] 
        public string $name,
        #[Groups(['private'])]
        public int $age,
    ) {} 
}

$dto = $mapper->map($entity, DtoWithGroups::class, allowGroups: ['public'], ignoreGroups: []);
```

If a property is filtered out and it is non-nullable without a default value — an exception will be thrown.

## Integration with container
- For Map(ifContainer) use services implementing MapIfCallableInterface
- For Map(transformContainer) use services implementing MapTransformCallableInterface
- You can also pass class names (if invokable) or [Class, 'method'] for non-container handlers; the mapper will instantiate classes directly (it does not fetch them from the container)

### Symfony service factory example
Below is a minimal example of how to wire ObjectToDtoMapper in a Symfony application and how to define callable services for Map(ifContainer) and Map(transformContainer).

```yaml
services:
  # 1) Register the mapper and pass the container to it
  Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper:
    arguments:
      $container: '@service_container'

  # 2) Define a service usable in Map(ifContainer)
  App\ObjectMapper\ShouldIncludeEmailIf:
    class: App\ObjectMapper\ShouldIncludeEmailIf

  # 3) Define a service usable in Map(transformContainer)
  App\ObjectMapper\UppercaseTransform:
    class: App\ObjectMapper\UppercaseTransform
```

Then implement the services:

```php
<?php declare(strict_types=1);

namespace App\ObjectMapper;

use Danilovl\ObjectDtoMapper\Attribute\MapIfCallableInterface;use Danilovl\ObjectDtoMapper\Attribute\MapTransformCallableInterface;

final class ShouldIncludeEmailIf implements MapIfCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): bool
    {
        return !empty($value);
    }
}

final class UppercaseTransform implements MapTransformCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): mixed
    {
        return $value === null ? null : mb_strtoupper((string) $value);
    }
}
```

Use them in your DTO:

```php
<?php declare(strict_types=1);

namespace App\ObjectMapper

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class UserDto
{
    public function __construct(
        #[Map(ifContainer: App\ObjectMapper\ShouldIncludeEmailIf::class)]
        public ?string $email,
        #[Map(transformContainer: App\ObjectMapper\UppercaseTransform::class)]
        public string $name,
    ) {}
}
```

## License
This package is open-sourced software licensed under the [MIT](https://opensource.org/licenses/MIT).

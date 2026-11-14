<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Attribute;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Exception\{
    MappingIfException,
    MappingTransformException
};
use PHPUnit\Framework\TestCase;

final class MapAttributeTest extends TestCase
{
    public function testConstructMinimal(): void
    {
        $attribute = new Map;

        $this->assertNull($attribute->target);
        $this->assertNull($attribute->source);
        $this->assertNull($attribute->if);
        $this->assertNull($attribute->ifContainer);
        $this->assertNull($attribute->transform);
        $this->assertNull($attribute->transformContainer);
    }

    public function testIfAndIfContainerTogetherThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage('Map(if) configuration invalid: cannot set both if and ifContainer at the same time');

        new Map(if: true, ifContainer: 'service.id');
    }

    public function testTransformAndTransformContainerTogetherThrows(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('Map(transform) configuration invalid: cannot set both transform and transformContainer at the same time');

        new Map(transform: static fn ($v) => $v, transformContainer: 'service.id');
    }

    public function testIfContainerEmptyStringThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage('Map(if) container definition must be a non-empty string');

        new Map(ifContainer: '');
    }

    public function testIfContainerWhitespaceStringThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage('Map(if) container definition must be a non-empty string');

        new Map(ifContainer: "  \t\n ");
    }

    public function testTransformContainerEmptyStringThrows(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('Map(transformContainer) container definition must be a non-empty string');

        new Map(transformContainer: '');
    }

    public function testTransformContainerWhitespaceStringThrows(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('Map(transformContainer) container definition must be a non-empty string');

        new Map(transformContainer: "  \t\n ");
    }

    public function testIfInvalidTypeThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage('Map(if) definition must be bool, string, array or Closure, int given');

        new Map(if: 1);
    }

    public function testTransformBoolThrows(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('Map(transform) definition must be string, array or Closure, bool given');

        new Map(transform: true);
    }

    public function testValidDefinitionsAreAccepted(): void
    {
        $closure = static fn (mixed $value): mixed => $value;

        $this->assertSame($closure, new Map(if: $closure)->if);
        $this->assertSame($closure, new Map(transform: $closure)->transform);
        $this->assertSame('trim', new Map(transform: 'trim')->transform);
        $this->assertSame(['A', 'b'], new Map(if: ['A', 'b'])->if);
        $this->assertFalse(new Map(if: false)->if);
    }
}

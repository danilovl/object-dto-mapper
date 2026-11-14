<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Exception;

use Danilovl\ObjectDtoMapper\Exception\{
    CircularReferenceException,
    MappingException,
    MappingIfException,
    MappingTransformException,
    MaxDepthException,
    NoSuchClassException,
    NoSuchPropertyException,
    RuntimeException,
    UnmappedObjectException
};
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException as PhpRuntimeException;

final class ExceptionHierarchyTest extends TestCase
{
    /**
     * @param class-string<MappingException> $class
     */
    #[DataProvider('provideEveryExceptionIsAMappingExceptionCases')]
    public function testEveryExceptionIsAMappingException(string $class): void
    {
        $exception = new $class('message');

        $this->assertInstanceOf(MappingException::class, $exception);
        $this->assertInstanceOf(PhpRuntimeException::class, $exception);
    }

    public static function provideEveryExceptionIsAMappingExceptionCases(): Generator
    {
        yield [CircularReferenceException::class];
        yield [MappingIfException::class];
        yield [MappingTransformException::class];
        yield [MaxDepthException::class];
        yield [NoSuchClassException::class];
        yield [NoSuchPropertyException::class];
        yield [RuntimeException::class];
        yield [UnmappedObjectException::class];
    }
}

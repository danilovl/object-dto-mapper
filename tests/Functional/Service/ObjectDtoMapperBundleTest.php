<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Functional\Service;

use Danilovl\ObjectDtoMapper\Exception\{
    MaxDepthException,
    UnmappedObjectException
};
use Danilovl\ObjectDtoMapper\Service\{
    ObjectToDtoMapper,
    ObjectToDtoMapperInterface
};
use Danilovl\ObjectDtoMapper\Tests\Functional\Kernel\TestKernel;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\{
    Holder,
    MockEntity
};
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\MockDtoIfContainerEmail;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform\MockDtoWithTransform;
use Danilovl\ObjectDtoMapper\Tests\Mock\Recursion\{
    CategoryDto,
    CategoryEntity
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Security\{
    MixedValueDto,
    UserEntity
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ObjectDtoMapperBundleTest extends TestCase
{
    private ?TestKernel $kernel = null;

    protected function tearDown(): void
    {
        if ($this->kernel !== null) {
            $cacheDir = $this->kernel->getCacheDir();
            $this->kernel->shutdown();
            new Filesystem()->remove(dirname($cacheDir));
            $this->kernel = null;
        }
    }

    /**
     * @param array<string, mixed> $bundleConfig
     */
    private function getMapper(array $bundleConfig = []): ObjectToDtoMapperInterface
    {
        $this->kernel = new TestKernel('test', false, $bundleConfig);
        $this->kernel->boot();

        /** @var ObjectToDtoMapperInterface $mapper */
        $mapper = $this->kernel->getContainer()->get(ObjectToDtoMapperInterface::class);

        return $mapper;
    }

    public function testMapperWorksWithoutAnyConfiguration(): void
    {
        $mapper = $this->getMapper();

        $this->assertInstanceOf(ObjectToDtoMapper::class, $mapper);
        $this->assertEquals(new MockDtoIfContainerEmail('John', 30, null), $mapper->map(new MockEntity('John', 30, null), MockDtoIfContainerEmail::class));
    }

    public function testPrivateHandlersWithoutAutoconfigureAndTagAreAvailable(): void
    {
        $mapper = $this->getMapper();

        $if = $mapper->map(new MockEntity('John', 30, 'john@example.com'), MockDtoIfContainerEmail::class);
        $this->assertSame('john@example.com', $if->email);

        $transform = $mapper->map(new MockEntity('john', 30, ' john@example.com '), MockDtoWithTransform::class);
        $this->assertSame('JOHN', $transform->name);
        $this->assertSame('JOHN@EXAMPLE.COM', $transform->email);
    }

    public function testDefaultsAreSafe(): void
    {
        $this->expectException(UnmappedObjectException::class);

        $this->getMapper()->map(new Holder(new UserEntity), MixedValueDto::class);
    }

    public function testBundleConfiguration(): void
    {
        $mapper = $this->getMapper(['allow_object_passthrough' => true, 'max_depth' => 2]);

        $entity = new UserEntity;
        $this->assertSame($entity, $mapper->map(new Holder($entity), MixedValueDto::class)->value);

        $this->expectException(MaxDepthException::class);
        $mapper->map(new CategoryEntity('c', new CategoryEntity('b', new CategoryEntity('a'))), CategoryDto::class);
    }
}

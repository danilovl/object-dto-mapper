<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\DependencyInjection\Compiler;

use Danilovl\ObjectDtoMapper\DependencyInjection\Compiler\MapHandlerCompilerPass;
use Danilovl\ObjectDtoMapper\DependencyInjection\ObjectDtoMapperExtension;
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockEntity;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\MockDtoIfContainerEmail;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    EmailNotEmptyMapIfService,
    UppercaseMapTransformService
};
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\{
    ChildDefinition,
    ContainerBuilder,
    Definition
};

final class MapHandlerCompilerPassTest extends TestCase
{
    private const string TAG = ObjectDtoMapperExtension::HANDLER_TAG;

    private function process(ContainerBuilder $container): void
    {
        new MapHandlerCompilerPass()->process($container);
    }

    public function testHandlersAreTaggedWithoutAutoconfigure(): void
    {
        $container = new ContainerBuilder;
        $container->register(EmailNotEmptyMapIfService::class, EmailNotEmptyMapIfService::class);
        $container->register(UppercaseMapTransformService::class, UppercaseMapTransformService::class);
        $container->register('unrelated', stdClass::class);

        $this->process($container);

        $this->assertTrue($container->getDefinition(EmailNotEmptyMapIfService::class)->hasTag(self::TAG));
        $this->assertTrue($container->getDefinition(UppercaseMapTransformService::class)->hasTag(self::TAG));
        $this->assertFalse($container->getDefinition('unrelated')->hasTag(self::TAG));
    }

    public function testClassIsResolvedFromServiceId(): void
    {
        $container = new ContainerBuilder;
        $container->setDefinition(EmailNotEmptyMapIfService::class, new Definition);

        $this->process($container);

        $this->assertTrue($container->getDefinition(EmailNotEmptyMapIfService::class)->hasTag(self::TAG));
    }

    public function testClassIsResolvedFromParameter(): void
    {
        $container = new ContainerBuilder;
        $container->setParameter('guard.class', EmailNotEmptyMapIfService::class);
        $container->register('guard', '%guard.class%');

        $this->process($container);

        $this->assertTrue($container->getDefinition('guard')->hasTag(self::TAG));
    }

    public function testClassIsResolvedFromParentDefinition(): void
    {
        $container = new ContainerBuilder;
        $container->register('parent', EmailNotEmptyMapIfService::class)->setAbstract(true);
        $container->setDefinition('child', new ChildDefinition('parent'));

        $this->process($container);

        $this->assertTrue($container->getDefinition('child')->hasTag(self::TAG));
        $this->assertFalse($container->getDefinition('parent')->hasTag(self::TAG));
    }

    public function testCircularParentChainIsIgnored(): void
    {
        $container = new ContainerBuilder;
        $container->setDefinition('a', new ChildDefinition('b'));
        $container->setDefinition('b', new ChildDefinition('a'));

        $this->process($container);

        $this->assertFalse($container->getDefinition('a')->hasTag(self::TAG));
    }

    public function testUnresolvableDefinitionsAreIgnored(): void
    {
        $container = new ContainerBuilder;
        $container->register('missing.class', 'App\Missing\Handler');
        $container->register('missing.parameter', '%missing.parameter%');
        $container->register('synthetic', EmailNotEmptyMapIfService::class)->setSynthetic(true);
        $container->setDefinition('orphan', new ChildDefinition('not.existing'));
        $container->setDefinition('no.class', new Definition);

        $this->process($container);

        foreach (['missing.class', 'missing.parameter', 'synthetic', 'orphan', 'no.class'] as $id) {
            $this->assertFalse($container->getDefinition($id)->hasTag(self::TAG), $id);
        }
    }

    public function testTagIsNotDuplicated(): void
    {
        $container = new ContainerBuilder;
        $container->register(EmailNotEmptyMapIfService::class, EmailNotEmptyMapIfService::class)->addTag(self::TAG);

        $this->process($container);

        $this->assertCount(1, $container->getDefinition(EmailNotEmptyMapIfService::class)->getTag(self::TAG));
    }

    public function testCompiledContainerProvidesPrivateHandlerToMapper(): void
    {
        $container = new ContainerBuilder;
        new ObjectDtoMapperExtension()->load([], $container);
        $container->register(EmailNotEmptyMapIfService::class, EmailNotEmptyMapIfService::class);
        $container->addCompilerPass(new MapHandlerCompilerPass);
        $container->compile();

        /** @var ObjectToDtoMapper $mapper */
        $mapper = $container->get(ObjectToDtoMapper::class);

        $this->assertNull($mapper->map(new MockEntity('John', 1, ''), MockDtoIfContainerEmail::class)->email);
        $this->assertSame('a@b.c', $mapper->map(new MockEntity('John', 1, 'a@b.c'), MockDtoIfContainerEmail::class)->email);
    }
}
